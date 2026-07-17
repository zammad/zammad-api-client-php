<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit;

use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
use ZammadAPIClient\Endpoints\Users\UserRepository;
use ZammadAPIClient\ZammadClient;

#[Group('unit')]
final class ZammadClientTest extends MockeryTestCase
{
    public function testRepoReturnsMemoizedRepositoryInstance(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        $first = $client->repo(TicketRepository::class);
        $second = $client->repo(TicketRepository::class);

        self::assertSame($first, $second);
        self::assertInstanceOf(TicketRepository::class, $first);
    }

    public function testRepoThrowsForUnknownRepositoryClass(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown repository');

        $client->repo('NotARepository');
    }

    public function testCallResolvesTicketRepository(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        $repo = @$client->ticket();

        self::assertInstanceOf(TicketRepository::class, $repo);
    }

    public function testCallResolvesUserRepository(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        $repo = @$client->user();

        self::assertInstanceOf(UserRepository::class, $repo);
    }

    public function testCallMemoizesRepository(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        self::assertSame(@$client->ticket(), @$client->ticket());
    }

    public function testCallThrowsForUnknownResource(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown resource');

        @$client->nonexistent();
    }

    public function testCallResolvesUnderscoreResources(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        $repo = @$client->ticket_article();

        self::assertInstanceOf(\ZammadAPIClient\Endpoints\TicketArticles\TicketArticleRepository::class, $repo);
    }

    public function testSetOnBehalfOfUserDelegatesToHandler(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->expects('setOnBehalfOfUser')->with(7)->once();

        $client = new ZammadClient($handler);
        $client->setOnBehalfOfUser(7);
    }

    public function testUnsetOnBehalfOfUserDelegatesToHandler(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->expects('setOnBehalfOfUser')->with(null)->once();

        $client = new ZammadClient($handler);
        $client->unsetOnBehalfOfUser();
    }

    public function testPerformOnBehalfOfExecutesCallbackAndResets(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->expects('getOnBehalfOfUser')->andReturn(null)->once();
        $handler->expects('setOnBehalfOfUser')->with(7)->once();
        $handler->expects('setOnBehalfOfUser')->with(null)->once();

        $client = new ZammadClient($handler);

        $result = $client->performOnBehalfOf(7, fn() => 42);

        self::assertSame(42, $result);
    }

    public function testPerformOnBehalfOfResetsOnException(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->expects('getOnBehalfOfUser')->andReturn(null)->once();
        $handler->expects('setOnBehalfOfUser')->with(7)->once();
        $handler->expects('setOnBehalfOfUser')->with(null)->once();

        $client = new ZammadClient($handler);

        $this->expectException(\RuntimeException::class);

        $client->performOnBehalfOf(7, function () {
            throw new \RuntimeException('test');
        });
    }

    public function testWithClientUsesInjectedHttpClient(): void
    {
        $httpClient = Mockery::mock(ClientInterface::class);
        $httpClient->expects('sendRequest')
            ->once()
            ->andReturnUsing(function (RequestInterface $request) {
                self::assertStringContainsString('tickets', (string) $request->getUri());

                return new \GuzzleHttp\Psr7\Response(200, [], (string) json_encode([
                    'tickets' => [
                        ['id' => 1, 'title' => 'T1', 'group_id' => 1],
                    ],
                ]));
            });

        $client = ZammadClient::withClient(
            $httpClient,
            new HttpFactory(),
            'https://zammad.example/api/v1',
        );

        $repo = $client->repo(TicketRepository::class);
        $tickets = iterator_to_array($repo->all());

        self::assertCount(1, $tickets);
        self::assertSame(1, $tickets[0]->id);
    }

    public function testWithClientThrowsWhenFactoryNotStreamFactory(): void
    {
        $factory = Mockery::mock(RequestFactoryInterface::class);
        $httpClient = Mockery::mock(ClientInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('implement both');

        ZammadClient::withClient(
            $httpClient,
            $factory,
            'https://zammad.example/api/v1',
        );
    }
}
