<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\DI;

use Maxa\Ondrej\Nette\GraphQL\Application\Application;
use Maxa\Ondrej\Nette\GraphQL\PsrCache\PsrCache;
use Maxa\Ondrej\Nette\GraphQL\PsrContainer\PsrContainer;
use Nette\DI\CompilerExtension;
use Nette\Loaders\RobotLoader;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use ReflectionClass;
use ReflectionMethod;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\SchemaFactory;
use function count;
use function sprintf;

/**
 * Class GraphQLiteExtension
 */
final class GraphQLiteExtension extends CompilerExtension {

    private const TEMP_DIR = 'tempDir';

    private const MAPPING = 'mapping';

    public function getConfigSchema(): Schema {
        return Expect::structure(
            [
                self::TEMP_DIR => Expect::string(),
                self::MAPPING => Expect::arrayOf(Expect::string(), Expect::string())->required()->min(1.0),
            ],
        )->required()->castTo('array');
    }

    public function beforeCompile(): void {
        /** @var array<string, mixed> $config */
        $config = $this->getConfig();
        $builder = $this->getContainerBuilder();

        $builder
            ->addDefinition($this->prefix('container'))
            ->setFactory(PsrContainer::class);

        $builder
            ->addDefinition($this->prefix('cache'))
            ->setFactory(PsrCache::class);

        $schemaFactory = $builder
            ->addDefinition($this->prefix('schemaFactory'))
            ->setFactory(SchemaFactory::class);

        $loader = new RobotLoader();
        $loader->setTempDirectory($config[self::TEMP_DIR]);
        foreach ($config[self::MAPPING] as $namespace => $dir) {
            $loader->addDirectory($dir);
            $schemaFactory
                ->addSetup('addControllerNamespace', [$namespace])
                ->addSetup('addTypeNamespace', [$namespace]);
        }

        $loader->refresh();
        /** @var array<class-string<object>,string> $classes */
        $classes = $loader->getIndexedClasses();
        foreach ($classes as $class => $file) {
            if ($this->hasQueriesOrMutations($class)) {
                $builder
                    ->addDefinition(null)
                    ->setFactory($class);
            }
        }

        $builder
            ->addDefinition($this->prefix('schema'))
            ->setFactory(sprintf('@%s::createSchema', $schemaFactory->getName()));

        $builder->addDefinition($this->prefix('application'))
            ->setFactory(Application::class);
    }

    /**
     * @param class-string<object> $reflectionClass
     */
    private function hasQueriesOrMutations(string $reflectionClass): bool {
        $reflection = new ReflectionClass($reflectionClass);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $refMethod) {
            if (count($refMethod->getAttributes(Query::class)) > 0) {
                return true;
            }

            if (count($refMethod->getAttributes(Mutation::class)) > 0) {
                return true;
            }
        }

        return false;
    }

}
