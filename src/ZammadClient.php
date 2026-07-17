<?php

declare(strict_types=1);

namespace ZammadAPIClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\NullLogger;
use ZammadAPIClient\Core\AbstractRepository;
use ZammadAPIClient\Core\ConnectionConfig;
use ZammadAPIClient\Core\Contracts\ClientInterface as ZammadClientInterface;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Core\RepositoryRegistry;
use ZammadAPIClient\Core\RequestHandler;

final class ZammadClient implements ZammadClientInterface
{
    public const USER_AGENT = 'Zammad API PHP';

    /** @var array<class-string, object> */
    private array $repos = [];

    public function __construct(
        private RequestHandlerInterface $handler,
    ) {
    }

    /**
     * Creates a client with token-based authentication (preferred).
     *
     *   $client = ZammadClient::withToken('https://zammad.example', 'your-token');
     */
    public static function withToken(
        string $url,
        string $token,
        ?ConnectionConfig $config = null,
    ): self {
        $config ??= new ConnectionConfig();

        return self::createClient($url, $config, "Token token={$token}");
    }

    /**
     * Creates a client with OAuth2 Bearer token authentication.
     *
     *   $client = ZammadClient::withOAuth2('https://zammad.example', 'your-oauth-token');
     */
    public static function withOAuth2(
        string $url,
        string $token,
        ?ConnectionConfig $config = null,
    ): self {
        $config ??= new ConnectionConfig();

        return self::createClient($url, $config, "Bearer {$token}");
    }

    /**
     * Creates a client with basic HTTP authentication.
     *
     *   $client = ZammadClient::withBasicAuth('https://zammad.example', 'admin@example.com', 'test');
     */
    public static function withBasicAuth(
        string $url,
        string $user,
        string $pass,
        ?ConnectionConfig $config = null,
    ): self {
        $config ??= new ConnectionConfig();

        return self::createClient($url, $config, 'Basic ' . base64_encode("{$user}:{$pass}"));
    }

    /**
     * Creates a client with a pre-configured PSR-18 HTTP client and PSR-17 factory.
     *
     * Use this when you need a non-Guzzle transport (e.g. Symfony HttpClient)
     * or custom middleware. The caller is responsible for setting auth headers
     * (Authorization, User-Agent) on the PSR-18 client.
     *
     *   $client = ZammadClient::withClient(
     *       $mySymfonyHttpClient,
     *       $myPsr17Factory,
     *       'https://zammad.example',
     *   );
     */
    public static function withClient(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        string $url,
        ?ConnectionConfig $config = null,
    ): self {
        if (!$requestFactory instanceof StreamFactoryInterface) {
            throw new InvalidArgumentException(
                'The factory must implement both RequestFactoryInterface and StreamFactoryInterface.',
            );
        }

        $config ??= new ConnectionConfig();

        $url = self::normalizeUrl($url);

        $handler = new RequestHandler(
            $httpClient,
            $requestFactory,
            $url,
            logger: $config->logger ?? new NullLogger(),
            maxRetries: $config->maxRetries,
        );

        return new self($handler);
    }

    /**
     * Returns the underlying PSR-18 request handler for raw API access.
     *
     * Use this escape hatch when you need to call an endpoint that has no
     * dedicated repository (e.g. ticket deletion via `$client->getHandler()->delete('tickets/1')`).
     */
    public function getHandler(): RequestHandlerInterface
    {
        return $this->handler;
    }

    private static function normalizeUrl(string $url): string
    {
        $url = rtrim($url, '/');

        if (!str_contains($url, '/api/')) {
            $url .= '/api/v1';
        }

        return $url;
    }

    private static function createClient(string $url, ConnectionConfig $config, string $authHeader): self
    {
        $url = self::normalizeUrl($url);

        $headers = [
            'User-Agent'    => self::USER_AGENT,
            'Authorization' => $authHeader,
        ];

        $httpClient = new GuzzleClient([
            'headers'         => $headers,
            'verify'          => $config->verifySsl,
            'timeout'         => $config->timeout,
            'connect_timeout' => $config->connectTimeout,
            'allow_redirects' => false,
        ]);

        $handler = new RequestHandler(
            $httpClient,
            new HttpFactory(),
            $url,
            logger: $config->logger ?? new NullLogger(),
            maxRetries: $config->maxRetries,
        );

        return new self($handler);
    }

    /**
     * Activates API-level impersonation for all subsequent requests.
     *
     * Forwards the identifier as `From` header. May be a user ID, login,
     * or email. The header stays active until unsetOnBehalfOfUser() is called.
     */
    public function setOnBehalfOfUser(int|string|null $userId): void
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
     * The header is set before the callback and restored afterwards.
     */
    public function performOnBehalfOf(int|string $userId, callable $callback): mixed
    {
        $previous = $this->handler->getOnBehalfOfUser();

        $this->handler->setOnBehalfOfUser($userId);

        try {
            return $callback($this);
        } finally {
            $this->handler->setOnBehalfOfUser($previous);
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
     * @deprecated Use {@see self::repo()} with an explicit repository class
     *             (e.g. `$client->repo(TicketRepository::class)`) for type-safe,
     *             IDE-friendly resource access. The magic method will be removed
     *             in v4.0.
     *
     * @param array<array-key, mixed> $args
     */
    public function __call(string $name, array $args): AbstractRepository
    {
        trigger_error(
            sprintf(
                'Magic resource accessor $client->%s() is deprecated. Use $client->repo(Repository::class) instead.',
                $name,
            ),
            E_USER_DEPRECATED,
        );

        $class = self::resolveAlias($name);

        if ($class !== null) {
            return $this->repo($class);
        }

        throw new InvalidArgumentException("Unknown resource: {$name}");
    }

    /** @return array<string, class-string<AbstractRepository>> */
    private static function aliasMap(): array
    {
        return [
            'ticket'           => \ZammadAPIClient\Endpoints\Tickets\TicketRepository::class,
            'user'             => \ZammadAPIClient\Endpoints\Users\UserRepository::class,
            'organization'     => \ZammadAPIClient\Endpoints\Organizations\OrganizationRepository::class,
            'group'            => \ZammadAPIClient\Endpoints\Groups\GroupRepository::class,
            'ticket_article'   => \ZammadAPIClient\Endpoints\TicketArticles\TicketArticleRepository::class,
            'ticket_state'     => \ZammadAPIClient\Endpoints\TicketStates\TicketStateRepository::class,
            'ticket_priority'  => \ZammadAPIClient\Endpoints\TicketPriorities\TicketPriorityRepository::class,
            'tag'              => \ZammadAPIClient\Endpoints\Tags\TagRepository::class,
            'text_module'      => \ZammadAPIClient\Endpoints\TextModules\TextModuleRepository::class,
            'link'             => \ZammadAPIClient\Endpoints\Links\LinkRepository::class,
        ];
    }

    /**
     * @return ?class-string<AbstractRepository>
     */
    private static function resolveAlias(string $name): ?string
    {
        return self::aliasMap()[$name] ?? null;
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
     * @param class-string<T>            $repoClass
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
