<?php declare(strict_types = 1);

namespace Maxa\Ondrej\Nette\GraphQL\PsrContainer;

use Nette\DI\Container;
use Nette\DI\MissingServiceException;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Class PsrContainer
 */
final class PsrContainer implements ContainerInterface {

    /**
     * PsrContainer constructor
     */
    public function __construct(private Container $container) {
    }

    /**
     * @throws PsrContainerException
     * @throws PsrContainerNotFoundException
     */
    public function get(mixed $id): mixed {
        try {
            return $this->container->getByType($id);
        } catch (MissingServiceException $exception) {
            throw new PsrContainerNotFoundException($exception->getMessage(), $exception->getCode(), $exception);
        } catch (Throwable $throwable) {
            throw new PsrContainerException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    public function has(mixed $id): bool {
        try {
            $this->container->getByType($id);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

}
