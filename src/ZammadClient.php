<?php

declare(strict_types=1);

namespace ZammadAPIClient;

use ZammadAPIClient\Core\Auth;
use ZammadAPIClient\Core\ConnectionConfig;
use ZammadAPIClient\Core\Contracts\ClientFactoryInterface;
use ZammadAPIClient\Core\Contracts\ClientInterface as ZammadClientInterface;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Factory\GuzzleClientFactory;
use ZammadAPIClient\Core\Repository\AbstractRepository;
use ZammadAPIClient\Core\Repository\RepositoryRegistry;
use ZammadAPIClient\Core\Traits\RepositoryAccessors;

/**
 * Entry point for the Zammad API.
 *
 *   $client = ZammadClient::create('https://zammad.example', Auth::token('your-token'));
 *
 *   $client->ticket()->find(1);
 *   $client->user()->all();
 *
 * To use a different HTTP transport, build the handler yourself and pass it to
 * the constructor — credentials and retry behaviour work exactly the same:
 *
 *   $handler = new RequestHandler($psr18, $psr17, $url, auth: Auth::token('your-token'));
 *   $client  = new ZammadClient($handler);
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

    /**
     * Creates a client for the given Zammad instance, using Guzzle as transport.
     *
     * This is the shortcut for the default transport, not the only full-featured
     * way in: passing your own {@see RequestHandlerInterface} to the constructor
     * supports the same credentials and options.
     *
     * @param string $url The Zammad base URL; `/api/v1` is appended if missing.
     */
    public static function create(
        string $url,
        Auth $auth,
        ?ConnectionConfig $config = null,
    ): self {
        return new self(new GuzzleClientFactory($url, $auth, $config));
    }

    public function getHandler(): RequestHandlerInterface
    {
        return $this->handler;
    }

    /**
     * Returns a memoized repository for the given repository class.
     *
     * @internal Use the typed accessors (e.g. {@see self::ticket()}) instead.
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
