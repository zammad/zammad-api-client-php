<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core\Traits;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use ZammadAPIClient\Core\RequestHandler;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait CreatesRequestHandler
{
    private const BASE_URL = 'https://zammad.example/api/v1';

    private HttpFactory $httpFactory;

    private function setUpRequestHandler(): void
    {
        $this->httpFactory = new HttpFactory();
    }

    private function createHandler(ClientInterface $client, int $maxRetries = 0): RequestHandler
    {
        return new RequestHandler($client, $this->httpFactory, self::BASE_URL, maxRetries: $maxRetries);
    }
}
