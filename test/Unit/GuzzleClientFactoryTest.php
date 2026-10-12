<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use ReflectionProperty;
use ZammadAPIClient\Core\Auth;
use ZammadAPIClient\Core\Contracts\ClientInterface;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Core\Transport\RequestHandler;
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

    /**
     * The three static helpers are shorthand for constructing the factory with an
     * explicit {@see Auth}, so these calls are deliberate: they pin down that both
     * forms stay equivalent. Do not remove them as leftovers.
     *
     * @return iterable<string, array{GuzzleClientFactory, GuzzleClientFactory}>
     */
    public static function equivalentFactories(): iterable
    {
        $url = 'https://zammad.example';

        yield 'token' => [
            GuzzleClientFactory::withToken($url, 'test-token'),
            new GuzzleClientFactory($url, Auth::token('test-token')),
        ];
        yield 'oauth2' => [
            GuzzleClientFactory::withOAuth2($url, 'oauth-token'),
            new GuzzleClientFactory($url, Auth::oauth2('oauth-token')),
        ];
        yield 'basic' => [
            GuzzleClientFactory::withBasicAuth($url, 'admin', 'test'),
            new GuzzleClientFactory($url, Auth::basic('admin', 'test')),
        ];
    }

    #[DataProvider('equivalentFactories')]
    public function testLegacyHelperMatchesExplicitAuthConstructor(
        GuzzleClientFactory $legacy,
        GuzzleClientFactory $explicit,
    ): void {
        self::assertSame(
            self::authHeaderOf($legacy->createHandler()),
            self::authHeaderOf($explicit->createHandler()),
        );
    }

    public function testZammadClientCreateUsesTheGivenAuth(): void
    {
        $client = ZammadClient::create('https://zammad.example', Auth::token('test-token'));

        self::assertSame('Token token=test-token', self::authHeaderOf($client->getHandler()));
    }

    /**
     * Reads the credential the handler will apply. White-box on purpose: the
     * Guzzle-backed path cannot be observed without making a real request.
     */
    private static function authHeaderOf(RequestHandlerInterface $handler): string
    {
        $auth = (new ReflectionProperty(RequestHandler::class, 'auth'))->getValue($handler);

        self::assertInstanceOf(Auth::class, $auth);

        return $auth->header();
    }
}
