<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Repositories;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;

#[Group('unit')]
final class TicketRepositoryTest extends MockeryTestCase
{
    public function testAllPaginatesUntilShortPage(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('tickets', ['page' => '1', 'per_page' => '2'])
            ->andReturn(['tickets' => [
                ['id' => 1, 'title' => 'a'],
                ['id' => 2, 'title' => 'b'],
            ]]);
        $handler->shouldReceive('get')
            ->once()
            ->with('tickets', ['page' => '2', 'per_page' => '2'])
            ->andReturn(['tickets' => [
                ['id' => 3, 'title' => 'c'],
            ]]);

        $repo = new TicketRepository($handler, 'tickets', TicketDTO::class, 2);

        $tickets = iterator_to_array($repo->all());

        self::assertCount(3, $tickets);
        self::assertContainsOnlyInstancesOf(TicketDTO::class, $tickets);
        self::assertSame([1, 2, 3], array_map(static fn(TicketDTO $t): ?int => $t->id, $tickets));
    }

    public function testDeleteDelegatesToHandler(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->expects('delete')
            ->with('tickets/42')
            ->once()
            ->andReturn([]);

        $repo = new TicketRepository($handler, 'tickets', TicketDTO::class);
        $repo->delete(42);
    }
}
