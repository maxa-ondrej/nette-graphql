<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\PsrCache;

use Exception;
use Psr\SimpleCache\CacheException;

/**
 * Class PsrCacheException
 */
final class PsrCacheException extends Exception implements CacheException {

}
