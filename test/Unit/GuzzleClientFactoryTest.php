<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\ClientInterface;
use ZammadAPIClient\Factory\GuzzleClientFactory;
use ZammadAPIClient\ZammadClient;

#[Group('unit')]
final class GuzzleClientFactoryTest extends MockeryTestCase
{
    public function testWithTokenReturnsGuzzleClientFactory(): void
    {
        $factory = GuzzleClientFactory::withToken('https://zammad.example', 'test-token');

        self::assertInstanceOf(GuzzleClientFactory::class, $factory);
    }

    public function testWithBasicAuthReturnsGuzzleClientFactory(): void
    {
        $factory = GuzzleClientFactory::withBasicAuth('https://zammad.example', 'admin', 'test');

        self::assertInstanceOf(GuzzleClientFactory::class, $factory);
    }

    public function testWithOAuth2ReturnsGuzzleClientFactory(): void
    {
        $factory = GuzzleClientFactory::withOAuth2('https://zammad.example', 'oauth-token');

        self::assertInstanceOf(GuzzleClientFactory::class, $factory);
    }

    public function testFactoryWrappedInZammadClient(): void
    {
        $client = new ZammadClient(
            GuzzleClientFactory::withToken('https://zammad.example', 'test-token'),
        );

        self::assertInstanceOf(ClientInterface::class, $client);
    }

    public function testNormalizeUrlAppendsApiV1(): void
    {
        $method = new \ReflectionMethod(GuzzleClientFactory::class, 'normalizeUrl');

        $cases = [
            'https://zammad.example'          => 'https://zammad.example/api/v1',
            'https://zammad.example/'         => 'https://zammad.example/api/v1',
            'https://zammad.example/api'      => 'https://zammad.example/api/v1',
            'https://zammad.example/api/'     => 'https://zammad.example/api/v1',
            'https://zammad.example/api/v1'   => 'https://zammad.example/api/v1',
            'https://zammad.example/api/v1/'  => 'https://zammad.example/api/v1',
            'https://zammad.example/api/v2'   => 'https://zammad.example/api/v2',
        ];

        foreach ($cases as $input => $expected) {
            self::assertSame($expected, $method->invoke(null, $input), "URL: {$input}");
        }
    }
}
