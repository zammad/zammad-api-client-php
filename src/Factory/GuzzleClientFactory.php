<?php

declare(strict_types=1);

namespace ZammadAPIClient\Factory;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Log\NullLogger;
use ZammadAPIClient\Core\Auth;
use ZammadAPIClient\Core\ConnectionConfig;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Core\Contracts\ClientFactoryInterface;
use ZammadAPIClient\Core\Transport\RequestHandler;

/**
 * Builds a Guzzle-backed {@see RequestHandler}.
 *
 * Most callers never touch this class — {@see \ZammadAPIClient\ZammadClient::create()}
 * wires it up for them. Use it directly only to hand a pre-built factory to the
 * `ZammadClient` constructor, e.g. in a DI container definition.
 */
final class GuzzleClientFactory implements ClientFactoryInterface
{
    public const USER_AGENT = 'Zammad API PHP';

    public function __construct(
        private readonly string $url,
        private readonly Auth $auth,
        private readonly ?ConnectionConfig $config = null,
    ) {
    }

    /**
     * Shorthand for `new self($url, Auth::token($token))`.
     */
    public static function withToken(
        string $url,
        string $token,
        ?ConnectionConfig $config = null,
    ): self {
        return new self($url, Auth::token($token), $config);
    }

    /**
     * Shorthand for `new self($url, Auth::oauth2($token))`.
     */
    public static function withOAuth2(
        string $url,
        string $token,
        ?ConnectionConfig $config = null,
    ): self {
        return new self($url, Auth::oauth2($token), $config);
    }

    /**
     * Shorthand for `new self($url, Auth::basic($user, $pass))`.
     */
    public static function withBasicAuth(
        string $url,
        string $user,
        #[\SensitiveParameter] string $pass,
        ?ConnectionConfig $config = null,
    ): self {
        return new self($url, Auth::basic($user, $pass), $config);
    }

    /**
     * Creates a request handler configured with this factory's credentials.
     *
     * The `Authorization` header is not set on the Guzzle client here — it is
     * applied per request by {@see RequestHandler}, which keeps authentication
     * working for any PSR-18 transport.
     */
    public function createHandler(): RequestHandlerInterface
    {
        $config = $this->config ?? new ConnectionConfig();

        $httpClient = new GuzzleClient([
            'headers'         => [
                'User-Agent' => self::USER_AGENT,
            ],
            'verify'          => $config->verifySsl,
            'timeout'         => $config->timeout,
            'connect_timeout' => $config->connectTimeout,
            'allow_redirects' => false,
        ]);

        return new RequestHandler(
            $httpClient,
            new HttpFactory(),
            $this->url,
            logger: $config->logger ?? new NullLogger(),
            maxRetries: $config->maxRetries,
            auth: $this->auth,
            allowInsecureHttp: $config->allowInsecureHttp,
        );
    }
}
