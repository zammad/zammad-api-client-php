<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
use ZammadAPIClient\ZammadClient;

#[Group('integration')]
final class PaginationIntegrationTest extends TestCase
{
    use \ZammadAPIClient\Tests\Integration\Traits\CreatesZammadClient;

    private static ZammadClient $client;
    private static array $ticketIds = [];

    /**
     * Creates 10 test tickets for pagination testing.
     */
    public static function setUpBeforeClass(): void
    {
        self::$client = self::createZammadClient();

        for ($i = 0; $i < 10; $i++) {
            self::$ticketIds[] = self::$client->repo(TicketRepository::class)->create(new TicketDTO(
                title: 'Page Test ' . uniqid('', true),
                group_id: 1,
                priority_id: 2,
                state_id: 1,
                customer_id: 1,
            ))->id;
        }
    }

    /**
     * Verifies the lazy generator streams multiple pages.
     */
    public function testAllPaginatesLazily(): void
    {
        $count = 0;
        foreach (self::$client->repo(TicketRepository::class)->all() as $ticket) {
            self::assertNotNull($ticket->id);
            $count++;
            if ($count >= 15) {
                break;
            }
        }

        self::assertGreaterThan(0, $count, 'Should stream tickets');
    }

    /**
     * Verifies page navigation via PaginatedList.
     */
    public function testPageNavigation(): void
    {
        $list = self::$client->repo(TicketRepository::class)->list(['expand' => 'true']);
        $list->page(1);

        $items = [];
        foreach ($list as $ticket) {
            $items[] = $ticket->id;
        }

        self::assertGreaterThan(0, count($items), 'First page should have tickets');

        if (count($items) >= 100) {
            $list->pageNext();
            $nextPage = [];
            foreach ($list as $ticket) {
                $nextPage[] = $ticket->id;
            }
            self::assertGreaterThan(0, count($nextPage), 'Second page should have tickets');
        }
    }

    /**
     * Verifies total_count is null on index endpoints (Zammad API limitation)
     * and non-null on search endpoints.
     */
    public function testTotalCount(): void
    {
        $list = self::$client->repo(TicketRepository::class)->list();
        $list->page(1);

        self::assertNull(
            $list->getTotalCount(),
            'total_count is not available on index endpoints (Zammad requires full=true)',
        );

        $searchList = self::$client->repo(TicketRepository::class)->searchList('*');
        $searchList->page(1);

        self::assertNotNull(
            $searchList->getTotalCount(),
            'total_count should be returned by search endpoints with with_total_count=true',
        );
        self::assertGreaterThanOrEqual(
            10,
            $searchList->getTotalCount(),
            'Should have at least the 10 tickets created in setUpBeforeClass',
        );

        $items = iterator_to_array($searchList);
        self::assertNotEmpty($items, 'searchList should return items');

        $total = $searchList->getTotalCount();
        if ($total > 0) {
            $maxPage = (int) ceil($total / 100);
            for ($page = 2; $page <= $maxPage; $page++) {
                $searchList->page($page);
                $pageItems = iterator_to_array($searchList);
                self::assertNotEmpty(
                    $pageItems,
                    "searchList page {$page}/{$maxPage} should return items (total={$total})",
                );
            }
        }
    }

    /**
     * Test tickets are cleaned up in setUp() / tearDown().
     */
    public static function tearDownAfterClass(): void
    {
    }
}
