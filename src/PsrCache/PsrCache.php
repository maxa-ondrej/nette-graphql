<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\PsrCache;

use Contributte\Cache\CacheFactory;
use Nette\Caching\Cache;
use Psr\SimpleCache\CacheInterface;
use Throwable;
use Traversable;
use function array_keys;
use function is_array;
use function is_string;
use function iterator_to_array;
use function strlen;

/**
 * Class PsrCache
 */
final class PsrCache implements CacheInterface {

    public const CACHE_NAMESPACE = 'PsrCache';

    private Cache $cache;

    /**
     * PsrCache constructor
     */
    public function __construct(CacheFactory $factory) {
        $this->cache = $factory->create(self::CACHE_NAMESPACE);
    }

    /**
     * @throws PsrCacheInvalidArgumentException|Throwable
     */
    public function get(mixed $key, mixed $default = null): mixed {
        return $this->cache->load($this->check($key), static fn () => $default);
    }

    /**
     * @throws PsrCacheInvalidArgumentException
     */
    public function set(mixed $key, mixed $value, mixed $ttl = null): bool {
        try {
            $this->cache->save($this->check($key), $value, [Cache::EXPIRE => $ttl]);

            return true;
        } catch (PsrCacheInvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @throws PsrCacheInvalidArgumentException
     */
    public function delete(mixed $key): bool {
        try {
            $this->cache->remove($this->check($key));

            return true;
        } catch (PsrCacheInvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable) {
            return false;
        }
    }

    public function clear(): bool {
        try {
            $this->cache->clean();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return iterable<mixed>
     *
     * @throws PsrCacheInvalidArgumentException
     */
    public function getMultiple(mixed $keys, mixed $default = null): iterable {
        return $this->cache->bulkLoad($this->checkMultiple($keys), static fn () => $default);
    }

    /**
     * @throws PsrCacheInvalidArgumentException
     */
    public function setMultiple(mixed $values, mixed $ttl = null): bool {
        if ($values instanceof Traversable) {
            $values = iterator_to_array($values);
        }

        $this->checkMultiple(array_keys($values));

        try {
            foreach ($values as $key => $item) {
                $this->cache->save($key, $item, [Cache::EXPIRE => $ttl]);
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @throws PsrCacheInvalidArgumentException
     */
    public function deleteMultiple(mixed $keys): bool {
        $this->checkMultiple($keys);

        try {
            foreach ($keys as $item) {
                $this->cache->remove($item);
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @throws PsrCacheInvalidArgumentException
     */
    public function has(mixed $item): bool {
        return $this->cache->load($this->check($item)) !== null;
    }

    /**
     * @throws PsrCacheInvalidArgumentException
     */
    private function check(mixed $key): string {
        if (!is_string($key) || strlen($key) === 0) {
            throw new PsrCacheInvalidArgumentException('PsrCache key must be string!');
        }

        return $key;
    }

    /**
     * @return array<mixed>
     *
     * @throws PsrCacheInvalidArgumentException
     */
    private function checkMultiple(mixed $keys): array {
        if ($keys instanceof Traversable) {
            $keys = iterator_to_array($keys);
        }

        if (!is_array($keys)) {
            throw new PsrCacheInvalidArgumentException('PsrCache keys must be array or instance of Traversable!');
        }

        foreach ($keys as $key) {
            $this->check($key);
        }

        return $keys;
    }

}
