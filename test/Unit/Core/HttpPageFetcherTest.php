<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Core\HttpPageFetcher;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;

#[Group('unit')]
final class HttpPageFetcherTest extends MockeryTestCase
{
    public function testFetchIndexEndpoint(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->expects('get')
            ->with('tickets', ['page' => '1', 'per_page' => '10'])
            ->once()
            ->andReturn(['tickets' => [['id' => 1, 'title' => 'Hello']]]);

        $fetcher = new HttpPageFetcher($handler, TicketDTO::class, 'tickets', 'tickets');

        $result = $fetcher->fetch(1, 10, []);

        self::assertCount(1, $result['items']);
        self::assertInstanceOf(TicketDTO::class, $result['items'][0]);
        self::assertSame(1, $result['items'][0]->id);
        self::assertNull($result['total_count']);
    }

    public function testFetchSearchEndpoint(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->expects('get')
            ->with('tickets/search', [
                'query' => 'hello',
                'page' => '1',
                'per_page' => '10',
                'with_total_count' => 'true',
            ])
            ->once()
            ->andReturn(['records' => [['id' => 1, 'title' => 'Hello']], 'total_count' => 42]);

        $fetcher = new HttpPageFetcher($handler, TicketDTO::class, 'tickets/search');

        $result = $fetcher->fetch(1, 10, ['query' => 'hello']);

        self::assertCount(1, $result['items']);
        self::assertSame(42, $result['total_count']);
    }

    public function testFetchMergesBaseQuery(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->expects('get')
            ->with('tickets', ['expand' => 'true', 'page' => '2', 'per_page' => '25'])
            ->once()
            ->andReturn(['tickets' => []]);

        $fetcher = new HttpPageFetcher($handler, TicketDTO::class, 'tickets', 'tickets');

        $result = $fetcher->fetch(2, 25, ['expand' => 'true']);

        self::assertEmpty($result['items']);
    }
}
