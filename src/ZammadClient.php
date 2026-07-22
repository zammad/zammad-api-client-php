<?php

declare(strict_types=1);

namespace ZammadAPIClient;

use ZammadAPIClient\Core\Contracts\ClientFactoryInterface;
use ZammadAPIClient\Core\Contracts\ClientInterface as ZammadClientInterface;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Core\Repository\AbstractRepository;
use ZammadAPIClient\Core\Repository\RepositoryRegistry;
use ZammadAPIClient\Core\Traits\RepositoryAccessors;

/**
 * Entry point for the Zammad API.
 *
 *   $client = new ZammadClient(
 *       GuzzleClientFactory::withToken('https://zammad.example', 'your-token'),
 *   );
 *
 *   $client->ticket()->find(1);
 *   $client->user()->all();
 *
 * @internal Prefer {@see ZammadClientInterface} for type hints.
 */
final class ZammadClient implements ZammadClientInterface
{
    use RepositoryAccessors;

    /** @var array<class-string, object> */
    private array $repos = [];

    private RequestHandlerInterface $handler;

    public function __construct(
        RequestHandlerInterface|ClientFactoryInterface $source,
    ) {
        $this->handler = $source instanceof ClientFactoryInterface
            ? $source->createHandler()
            : $source;
    }

    public function getHandler(): RequestHandlerInterface
    {
        return $this->handler;
    }

    /**
     * Returns a memoized repository for the given repository class.
     *
     * @template T of AbstractRepository
     * @param class-string<T> $repositoryClass
     * @return T
     */
    public function repo(string $repositoryClass): AbstractRepository
    {
        if (!isset($this->repos[$repositoryClass])) {
            $definition = RepositoryRegistry::definition($repositoryClass);

            $this->repos[$repositoryClass] = new $repositoryClass(
                $this->handler,
                $definition['path'],
                $definition['dto'],
            );
        }

        /** @var T */
        return $this->repos[$repositoryClass];
    }
}
