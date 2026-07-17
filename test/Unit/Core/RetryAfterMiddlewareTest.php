<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ZammadAPIClient\Core\RetryAfterMiddleware;

#[Group('unit')]
final class RetryAfterMiddlewareTest extends TestCase
{
    public function testPassesThroughNon429Response(): void
    {
        $inner = new class implements ClientInterface {
            public int $callCount = 0;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->callCount++;

                return new Response(200, [], '{"ok":true}');
            }
        };

        $middleware = new RetryAfterMiddleware($inner, maxRetries: 3, defaultDelay: 0);
        $response = $middleware->sendRequest(new Request('GET', 'test'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(1, $inner->callCount);
    }

    public function testRetriesOn429WithRetryAfter(): void
    {
        $inner = new class implements ClientInterface {
            public int $callCount = 0;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->callCount++;
                if ($this->callCount === 1) {
                    return new Response(429, ['Retry-After' => '0'], '');
                }

                return new Response(200, [], '{"ok":true}');
            }
        };

        $middleware = new RetryAfterMiddleware($inner, maxRetries: 3, defaultDelay: 0);
        $response = $middleware->sendRequest(new Request('GET', 'test'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(2, $inner->callCount);
    }

    public function testRetriesOn429WithoutRetryAfterUsesDefaultDelay(): void
    {
        $inner = new class implements ClientInterface {
            public int $callCount = 0;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->callCount++;
                if ($this->callCount === 1) {
                    return new Response(429, [], '');
                }

                return new Response(200, [], '{"ok":true}');
            }
        };

        $middleware = new RetryAfterMiddleware($inner, maxRetries: 3, defaultDelay: 0);
        $response = $middleware->sendRequest(new Request('GET', 'test'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(2, $inner->callCount);
    }

    public function testReturns429AfterExhaustingRetries(): void
    {
        $inner = new class implements ClientInterface {
            public int $callCount = 0;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->callCount++;

                return new Response(429, ['Retry-After' => '0'], '');
            }
        };

        $middleware = new RetryAfterMiddleware($inner, maxRetries: 2, defaultDelay: 0);
        $response = $middleware->sendRequest(new Request('GET', 'test'));

        self::assertSame(429, $response->getStatusCode());
        self::assertSame(2, $inner->callCount); // 1 initial + 1 retry (maxRetries=2)
    }

    public function testPostWithBodyRewoundOnRetry(): void
    {
        $inner = new class implements ClientInterface {
            public int $callCount = 0;
            public ?string $lastBody = null;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->callCount++;
                $this->lastBody = (string) $request->getBody();

                if ($this->callCount === 1) {
                    return new Response(429, ['Retry-After' => '0'], '');
                }

                return new Response(200, [], '{"ok":true}');
            }
        };

        $middleware = new RetryAfterMiddleware($inner, maxRetries: 3, defaultDelay: 0);

        $request = new Request('POST', 'test', [], '{"title":"hello"}');
        $response = $middleware->sendRequest($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(2, $inner->callCount);
        self::assertSame('{"title":"hello"}', $inner->lastBody);
    }
}
