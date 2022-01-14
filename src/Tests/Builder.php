<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\Tests;

interface Builder {

    public function addType(string $name, ?string $alias = null): TypeBuilder;

    public function build(): string;

    public function parent(): Builder;

}
