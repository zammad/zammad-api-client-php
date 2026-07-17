<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Repositories;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\TicketPriorities\TicketPriorityDTO;
use ZammadAPIClient\Endpoints\TicketPriorities\TicketPriorityRepository;

#[Group('unit')]
final class TicketPriorityRepositoryTest extends MockeryTestCase
{
    public function testAllPaginatesTicketPriorities(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('ticket_priorities', ['page' => '1', 'per_page' => '2'])
            ->andReturn(['ticket_priorities' => [
                ['id' => 1, 'name' => '3 high'],
                ['id' => 2, 'name' => '2 normal'],
            ]]);
        $handler->shouldReceive('get')
            ->once()
            ->with('ticket_priorities', ['page' => '2', 'per_page' => '2'])
            ->andReturn([]);

        $repo = new TicketPriorityRepository($handler, 'ticket_priorities', TicketPriorityDTO::class, 2);
        $priorities = iterator_to_array($repo->all());

        self::assertCount(2, $priorities);
        self::assertContainsOnlyInstancesOf(TicketPriorityDTO::class, $priorities);
    }
}
