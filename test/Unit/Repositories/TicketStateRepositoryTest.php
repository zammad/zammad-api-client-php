<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Repositories;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\TicketStates\TicketStateDTO;
use ZammadAPIClient\Endpoints\TicketStates\TicketStateRepository;

#[Group('unit')]
final class TicketStateRepositoryTest extends MockeryTestCase
{
    public function testAllPaginatesTicketStates(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('ticket_states', ['page' => '1', 'per_page' => '2'])
            ->andReturn(['ticket_states' => [
                ['id' => 1, 'name' => 'open'],
                ['id' => 2, 'name' => 'closed'],
            ]]);
        $handler->shouldReceive('get')
            ->once()
            ->with('ticket_states', ['page' => '2', 'per_page' => '2'])
            ->andReturn([]);

        $repo = new TicketStateRepository($handler, 'ticket_states', TicketStateDTO::class, 2);
        $states = iterator_to_array($repo->all());

        self::assertCount(2, $states);
        self::assertContainsOnlyInstancesOf(TicketStateDTO::class, $states);
    }

    public function testFindReturnsTicketStateDto(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('ticket_states/1', ['expand' => 'true'])
            ->andReturn(['id' => 1, 'name' => 'open']);

        $repo = new TicketStateRepository($handler, 'ticket_states', TicketStateDTO::class);
        $state = $repo->find(1);

        self::assertSame(1, $state->id);
        self::assertSame('open', $state->name);
    }
}
