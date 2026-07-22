<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit;

use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\Groups\GroupRepository;
use ZammadAPIClient\Endpoints\Links\LinkRepository;
use ZammadAPIClient\Endpoints\Organizations\OrganizationRepository;
use ZammadAPIClient\Endpoints\Tags\TagRepository;
use ZammadAPIClient\Endpoints\TextModules\TextModuleRepository;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleRepository;
use ZammadAPIClient\Endpoints\TicketPriorities\TicketPriorityRepository;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
use ZammadAPIClient\Endpoints\TicketStates\TicketStateRepository;
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

    public function testOrganizationAccessorDelegatesToRepo(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        self::assertInstanceOf(OrganizationRepository::class, $client->organization());
    }

    public function testGroupAccessorDelegatesToRepo(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        self::assertInstanceOf(GroupRepository::class, $client->group());
    }

    public function testTicketArticleAccessorDelegatesToRepo(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        self::assertInstanceOf(TicketArticleRepository::class, $client->ticketArticle());
    }

    public function testTicketStateAccessorDelegatesToRepo(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        self::assertInstanceOf(TicketStateRepository::class, $client->ticketState());
    }

    public function testTicketPriorityAccessorDelegatesToRepo(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        self::assertInstanceOf(TicketPriorityRepository::class, $client->ticketPriority());
    }

    public function testTagAccessorDelegatesToRepo(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        self::assertInstanceOf(TagRepository::class, $client->tag());
    }

    public function testTextModuleAccessorDelegatesToRepo(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        self::assertInstanceOf(TextModuleRepository::class, $client->textModule());
    }

    public function testLinkAccessorDelegatesToRepo(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        self::assertInstanceOf(LinkRepository::class, $client->link());
    }
}
