<?php

declare(strict_types=1);

namespace ZammadAPIClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use ZammadAPIClient\Core\Contracts\ClientInterface;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Core\AbstractRepository;
use ZammadAPIClient\Core\RepositoryRegistry;
use ZammadAPIClient\Core\RequestHandler;
use ZammadAPIClient\Core\RetryAfterMiddleware;

final class ZammadClient implements ClientInterface
{
    public const USER_AGENT = 'Zammad API PHP';

    /** @var array<class-string, object> */
    private array $repos = [];

    public function __construct(
        private RequestHandlerInterface $handler,
    ) {
    }

    /**
     * Convenience factory with multiple authentication modes.
     *
     * Token auth (preferred):
     *   ZammadClient::connect($url, token: 'your-token')
     *
     * OAuth2:
     *   ZammadClient::connect($url, oauth2: 'your-token')
     *
     * Basic auth:
     *   ZammadClient::connect($url, user: 'admin@example.com', pass: 'test')
     *
     * @param bool $debug      Enables Guzzle debug mode (request/response body to stdout).
     * @param bool $verifySsl  false to disable TLS certificate verification.
     */
    public static function connect(
        string $url,
        ?string $token = null,
        ?string $oauth2 = null,
        ?string $user = null,
        ?string $pass = null,
        int $maxRetries = 3,
        bool $debug = false,
        bool $verifySsl = true,
    ): self {
        $headers = ['User-Agent' => self::USER_AGENT];

        if ($token !== null && $token !== '' && $token !== '0') {
            $headers['Authorization'] = "Token token={$token}";
        } elseif ($oauth2 !== null && $oauth2 !== '' && $oauth2 !== '0') {
            $headers['Authorization'] = "Bearer {$oauth2}";
        } elseif ($user !== null && $pass !== null) {
            $headers['Authorization'] = 'Basic ' . base64_encode("{$user}:{$pass}");
        } else {
            throw new InvalidArgumentException(
                'Provide one of: token, oauth2, or user+pass for authentication.'
            );
        }

        $http = new RetryAfterMiddleware(
            new GuzzleClient([
                'headers' => $headers,
                'debug'   => $debug,
                'verify'  => $verifySsl,
            ]),
            maxRetries: $maxRetries,
        );

        $factory = new HttpFactory();
        $handler = new RequestHandler($http, $factory, $factory, $url);

        return new self($handler);
    }

    /**
     * Activates API-level impersonation for all subsequent requests.
     *
     * Forwards $userId as X-On-Behalf-Of header. The header stays active
     * until unsetOnBehalfOfUser() is called.
     */
    public function setOnBehalfOfUser(?int $userId): void
    {
        $this->handler->setOnBehalfOfUser($userId);
    }

    public function unsetOnBehalfOfUser(): void
    {
        $this->handler->setOnBehalfOfUser(null);
    }

    /**
     * Executes a closure with a temporary impersonation header.
     *
     * Ruby-style: client.perform_on_behalf_of(user_id) { ... }
     * The header is set before the callback and cleared afterwards.
     */
    public function performOnBehalfOf(int $userId, callable $callback): mixed
    {
        $previous = null;

        $this->handler->setOnBehalfOfUser($userId);

        try {
            return $callback($this);
        } finally {
            $this->handler->setOnBehalfOfUser(null);
        }
    }

    /**
     * Ruby-style resource accessor: $client->ticket()->find(1)
     *
     * Maps the method name to a repository using RepositoryRegistry
     * via DTO class name lookup. Example:
     *   ticket()    → TicketRepository
     *   user()      → UserRepository
     *   group()     → GroupRepository
     *   ticket_article() → TicketArticleRepository
     *
     * @param array<array-key, mixed> $args
     */
    public function __call(string $name, array $args): AbstractRepository
    {
        $camel = implode('', array_map('ucfirst', explode('_', $name)));

        foreach (RepositoryRegistry::DEFINITIONS as $repoClass => $def) {
            $dtoShort = substr(strrchr($def['dto'], '\\') ?: '', 1);
            $repoShort = substr(strrchr($repoClass, '\\') ?: '', 1);

            if (
                strcasecmp($dtoShort, $camel . 'DTO') === 0
                || strcasecmp($repoShort, $camel . 'Repository') === 0
                || strcasecmp($dtoShort, $camel) === 0
            ) {
                return $this->repo($repoClass);
            }
        }

        throw new InvalidArgumentException("Unknown resource: {$name}");
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
        $definition = RepositoryRegistry::definition($repositoryClass);

        /** @var T */
        return $this->repository($repositoryClass, $definition['path'], $definition['dto']);
    }

    /**
     * @template T of AbstractRepository
     * @param class-string<T>           $repoClass
     * @param class-string<DTOInterface> $dtoClass
     * @return T
     */
    private function repository(string $repoClass, string $path, string $dtoClass): object
    {
        if (!isset($this->repos[$repoClass])) {
            $this->repos[$repoClass] = new $repoClass($this->handler, $path, $dtoClass);
        }

        /** @var T $repo */
        $repo = $this->repos[$repoClass];

        return $repo;
    }
}
