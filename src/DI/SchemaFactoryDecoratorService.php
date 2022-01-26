<?php

namespace Maxa\Ondrej\Nette\GraphQL\DI;

use TheCodingMachine\GraphQLite\SchemaFactory;

interface SchemaFactoryDecoratorService {

    public function decorate(SchemaFactory $schemaFactory): void;

}