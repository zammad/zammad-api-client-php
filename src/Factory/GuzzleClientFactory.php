<?php

declare(strict_types=1);

namespace ZammadAPIClient\Factory;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Log\NullLogger;
use ZammadAPIClient\Core\ConnectionConfig;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Core\Contracts\ClientFactoryInterface;
use ZammadAPIClient\Core\Transport\RequestHandler;

final class GuzzleClientFactory implements ClientFactoryInterface
{
    public const USER_AGENT = 'Zammad API PHP';

    private function __construct(
        private readonly string $url,
        private readonly string $authHeader,
        private ?ConnectionConfig $config = null,
    ) {
    }

    public static function withToken(
        string $url,
        string $token,
        ?ConnectionConfig $config = null,
    ): self {
        return new self($url, "Token token={$token}", $config);
    }

    public static function withOAuth2(
        string $url,
        string $token,
        ?ConnectionConfig $config = null,
    ): self {
        return new self($url, "Bearer {$token}", $config);
    }

    public static function withBasicAuth(
        string $url,
        string $user,
        string $pass,
        ?ConnectionConfig $config = null,
    ): self {
        return new self($url, 'Basic ' . base64_encode("{$user}:{$pass}"), $config);
    }

    /**
     * Creates a request handler configured with this factory's credentials.
     */
    public function createHandler(): RequestHandlerInterface
    {
        $config = $this->config ?? new ConnectionConfig();

        $httpClient = new GuzzleClient([
            'headers'         => [
                'User-Agent'    => self::USER_AGENT,
                'Authorization' => $this->authHeader,
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
        );
    }
}
