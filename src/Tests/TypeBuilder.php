<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\Tests;

use Nette\Utils\Arrays;
use function count;
use function implode;
use function is_string;
use function str_repeat;

final class TypeBuilder implements Builder {

    /** @var array<string, string> */
    private array $arguments = [];

    /** @var array<TypeBuilder> */
    private array $children = [];

    public function __construct(
        private Builder $parent,
        private int $indent,
        private string $name,
        private ?string $alias = null,
    ) {
    }

    public function parent(): Builder {
        return $this->parent;
    }

    public function addArgument(string $name, string $value): TypeBuilder {
        $this->arguments[$name] = $value;

        return $this;
    }

    public function addTextArgument(string $name, string $value): TypeBuilder {
        return $this->addArgument($name, "\"$value\"");
    }

    public function addType(string $name, ?string $alias = null): TypeBuilder {
        $builder = new TypeBuilder($this, $this->indent + 1, $name, $alias);
        $this->children[] = $builder;

        return $builder;
    }

    public function build(): string {
        $indent = str_repeat('    ', $this->indent);
        $stringBuilder = $indent;
        if (is_string($this->alias)) {
            $stringBuilder .= "$this->alias: ";
        }

        $stringBuilder .= $this->name;
        if (count($this->arguments) > 0) {
            $arguments = implode(
                ',',
                Arrays::map($this->arguments, static fn (string $value, string $name) => "$name: $value"),
            );
            $stringBuilder .= "($arguments)";
        }

        if (count($this->children) > 0) {
            $children = implode(
                "\n",
                Arrays::map($this->children, static fn (TypeBuilder $value) => $value->build()),
            );
            $stringBuilder .= " {\n$children\n$indent}";
        }

        return $stringBuilder;
    }

}
