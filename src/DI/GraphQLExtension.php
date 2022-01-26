<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\DI;

use Maxa\Ondrej\Nette\GraphQL\Application\Application;
use Maxa\Ondrej\Nette\GraphQL\PsrCache\PsrCache;
use Maxa\Ondrej\Nette\GraphQL\PsrContainer\PsrContainer;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\Loaders\RobotLoader;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use ReflectionClass;
use ReflectionMethod;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\SchemaFactory;
use Tracy\Debugger;
use function count;
use function sprintf;

/**
 * Class GraphQLiteExtension
 */
final class GraphQLExtension extends CompilerExtension {

    public function beforeCompile(): void {
        $global = $this->compiler->getConfig()['parameters'];
        $builder = $this->getContainerBuilder();

        $builder
            ->addDefinition($this->prefix('container'))
            ->setFactory(PsrContainer::class);

        $builder
            ->addDefinition($this->prefix('cache'))
            ->setFactory(PsrCache::class);

        $loader = (new RobotLoader())
            ->setTempDirectory($global['tempDir'])
            ->addDirectory($global['appDir']);
        $loader->refresh();

        /** @var array<class-string<object>,string> $classes */
        $classes = $loader->getIndexedClasses();

        /** @var string $cls */
        $cls = array_key_last($classes);
        $file = array_values(array_reverse($classes))[0];
        $appNamespace = self::getAppNamespace($cls, $file, $global['appDir']);

        $schemaFactory = $builder
            ->addDefinition($this->prefix('schemaFactory'))
            ->setFactory(SchemaFactory::class)
            ->addSetup('addControllerNamespace', [$appNamespace])
            ->addSetup('addTypeNamespace', [$appNamespace])
            ->addSetup(Debugger::$productionMode ? 'prodMode' : 'devMode');

        foreach (array_keys($classes) as $class) {
            $this->handleClass($class, $schemaFactory);
        }

        $builder
            ->addDefinition($this->prefix('schema'))
            ->setFactory(sprintf('@%s::createSchema', $schemaFactory->getName()));

        $builder->addDefinition($this->prefix('application'))
            ->setFactory(Application::class);
    }

    public static function getAppNamespace(string $cls, string $file, string $appDir): string {
        $relative = str_replace([$appDir, '.php', '/'], ['', '', '\\'], $file);
        return str_replace($relative, '', $cls);
    }

    /**
     * @param class-string<object> $class
     */
    private function handleClass(string $class, ServiceDefinition $schemaFactory): void {
        $reflection = new ReflectionClass($class);
        $builder = $this->getContainerBuilder();

        if (self::hasAttribute($reflection, Authentication::class)) {
            $service = $this->prefix('authentication');
            $builder->addDefinition($service)->setFactory($class);
            $schemaFactory->addSetup('setAuthenticationService', ["@$service"]);
        }

        if (self::hasAttribute($reflection, Authorization::class)) {
            $service = $this->prefix('authorization');
            $builder->addDefinition($service)->setFactory($class);
            $schemaFactory->addSetup('setAuthorizationService', ["@$service"]);
        }

        if (self::hasAttribute($reflection, Middleware::class)) {
            $attribute = $reflection->getAttributes(Middleware::class)[0]->newInstance();
            assert($attribute instanceof Middleware);
            $builder->addDefinition(null)->setFactory($class);
            switch ($attribute->type) {
                case Middleware::FIELD:
                    $schemaFactory->addSetup('addFieldMiddleware', ["@$class"]);
                    break;
                case Middleware::PARAMETER:
                    $schemaFactory->addSetup('addParameterMiddleware', ["@$class"]);
            }
        }

        if (self::hasAttribute($reflection, FactoryDecorator::class)) {
            $builder->addDefinition(null)->setFactory($class);
            $schemaFactory->addSetup("@$class::decorate", [
                new Statement(SchemaFactory::class),
            ]);
        }

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $refMethod) {
            if (self::hasAttribute($refMethod, Query::class) || self::hasAttribute($refMethod, Mutation::class)) {
                $builder
                    ->addDefinition(null)
                    ->setFactory($class);
                break;
            }
        }

    }

    public static function hasAttribute(ReflectionClass|ReflectionMethod $reflection, string $attribute): bool {
        return count($reflection->getAttributes($attribute)) > 0;
    }

}
