<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\PsrCache;

use Exception;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class PsrCacheInvalidArgumentException
 */
final class PsrCacheInvalidArgumentException extends Exception implements InvalidArgumentException {

}
