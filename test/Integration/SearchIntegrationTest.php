<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
use ZammadAPIClient\ZammadClient;

#[Group('integration')]
final class SearchIntegrationTest extends TestCase
{
    use \ZammadAPIClient\Tests\Integration\Traits\CreatesZammadClient;

    private static ZammadClient $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = self::createZammadClient();
    }

    /**
     * Verifies search() returns a valid iterable regardless of backend.
     */
    public function testSearchIsIterable(): void
    {
        $results = self::$client->repo(TicketRepository::class)->search('some text');

        self::assertIsIterable($results);

        foreach ($results as $ticket) {
            self::assertInstanceOf(TicketDTO::class, $ticket);
            break;
        }
    }

    /**
     * Search with no matches still returns a valid iterable.
     */
    public function testSearchReturnsEmptyIteratorOnNoMatch(): void
    {
        $result = self::$client->repo(TicketRepository::class)->search('xyznonexistent_' . uniqid('', true));

        self::assertIsIterable($result);

        $count = 0;
        foreach ($result as $ticket) {
            $count++;
        }

        self::assertSame(0, $count, 'Search with no match should yield zero items');
    }
}
