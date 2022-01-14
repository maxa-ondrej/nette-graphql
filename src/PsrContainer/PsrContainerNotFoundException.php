<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\PsrContainer;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class PsrContainerNotFoundException
 */
final class PsrContainerNotFoundException extends Exception implements NotFoundExceptionInterface {

}
