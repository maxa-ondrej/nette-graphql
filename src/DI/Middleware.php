<?php declare(strict_types=1);

namespace Maxa\Ondrej\Nette\GraphQL\DI;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Middleware {

    public const FIELD = 0;
    public const PARAMETER = 1;

    public int $type;

}