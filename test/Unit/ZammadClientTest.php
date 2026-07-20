<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit;

use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
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

    public function testGetHandlerReturnsInjectedHandler(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        self::assertSame($handler, $client->getHandler());
    }

    public function testTicketAccessorDelegatesToRepo(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        $ticketRepo = $client->ticket();

        self::assertInstanceOf(TicketRepository::class, $ticketRepo);
    }

    public function testUserAccessorDelegatesToRepo(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        self::assertInstanceOf(UserRepository::class, $client->user());
    }
}
