<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\Tests;

use Nette\Utils\Arrays;
use function implode;

final class QueryBuilder implements Builder {

    /** @var array<TypeBuilder> */
    private array $queries = [];

    public function addType(string $name, ?string $alias = null): TypeBuilder {
        $builder = new TypeBuilder($this, 1, $name, $alias);
        $this->queries[] = $builder;

        return $builder;
    }

    public function build(): string {
        $children = implode(
            "\n",
            Arrays::map($this->queries, static fn (TypeBuilder $value) => $value->build()),
        );

        return "{\n$children\n}";
    }

    public function parent(): Builder {
        return $this;
    }

}
