# [**Nette Framework**](https://github.com/nette/nette) [**GraphQLite**](https://github.com/thecodingmachine/graphqlite) Extension
[![Downloads](https://img.shields.io/packagist/dt/maxa-ondrej/nette-graphqlite.svg?style=flat-square)](https://packagist.org/packages/maxa-ondrej)
[![Build Status](https://img.shields.io/travis/maxa-ondrej/nette-graphqlite.svg?style=flat-square)](https://travis-ci.org/maxa-ondrej)
[![Coverage Status](https://img.shields.io/coveralls/github/maxa-ondrej/coding-standard.svg?style=flat-square)](https://coveralls.io/github/maxa-ondrej)
[![Latest Stable Version](https://img.shields.io/github/release/maxa-ondrej/nette-graphqlite.svg?style=flat-square)](https://github.com/maxa-ondrej/releases)

**Usage**
```
composer require maxa-ondrej/nette-graphql
```

**Nette Framework Usage**

***config.neon***
```yml
extensions:
    graphql: Maxa\Ondrej\Nette\GraphQL\DI\GraphQLExtension
```

***MyPresenter.php***

```php
<?php declare(strict_types=1);

namespace App\Presenters;

use TheCodingMachine\GraphQLite\Annotations\Query;

/**
 * Class MyPresenter
 *
 * @package App\Presenters
 */
final class MyPresenter {

    /**
     * GraphQL request example:
     * {
     *   echo(name: "World")
     * }
     * outputs -> "Hello World" 
     */
    #[Query]
    public function echo(string $name): string {
        return 'Hello '. $name;    
    }
    
}
```

### Want to modify the Schema Factory instance?
Use the already predefined class attributes:
* `#[Authentication]` -> class must implement `TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface`
* `#[Authorization]` -> class must implement `TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface`
* `#[Middleware(Middleware::FIELD)]` -> class must implement `TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface`
* `#[Middleware(Middleware::PARAMETER)]` -> class must implement `TheCodingMachine\GraphQLite\Middlewares\ParameterMiddlewareInterface`

Or use a custom implementation of `Maxa\Ondrej\Nette\GraphQL\DI\SchemaFactoryDecoratorService` and add class attribute `#[FactoryDecorator]`.


***GraphQLite documentation***
- [Queries](https://graphqlite.thecodingmachine.io/docs/queries)
- [Mutations](https://graphqlite.thecodingmachine.io/docs/mutations)
- [Input types](https://graphqlite.thecodingmachine.io/docs/input-types)
- [Output types](https://graphqlite.thecodingmachine.io/docs/type_mapping)
