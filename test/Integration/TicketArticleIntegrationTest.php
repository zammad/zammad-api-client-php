<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleDTO;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleRepository;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
use ZammadAPIClient\ZammadClient;

#[Group('integration')]
final class TicketArticleIntegrationTest extends TestCase
{
    use \ZammadAPIClient\Tests\Integration\Traits\CreatesZammadClient;

    private static ZammadClient $client;
    private static int $ticketId;

    public static function setUpBeforeClass(): void
    {
        self::$client = self::createZammadClient();

        $ticket = self::$client->repo(TicketRepository::class)->create(new TicketDTO(
            title: 'Article Test ' . uniqid('', true),
            customer_id: 1,
            group_id: 1,
            state_id: 1,
            priority_id: 2,
            article: [
                'subject' => 'Test article',
                'body'    => 'Created for article integration test',
                'type'    => 'note',
            ],
        ));
        self::$ticketId = $ticket->id;
    }

    public static function tearDownAfterClass(): void
    {
        self::$client->repo(TicketRepository::class)->delete(self::$ticketId);
    }

    /**
     * Fetches articles for a created ticket via the dedicated by_ticket endpoint.
     */
    public function testGetForTicket(): void
    {
        $count = 0;
        foreach (self::$client->repo(TicketArticleRepository::class)->getForTicket(self::$ticketId) as $article) {
            self::assertInstanceOf(TicketArticleDTO::class, $article);
            self::assertSame(self::$ticketId, $article->ticket_id);
            $count++;
        }

        self::assertGreaterThan(0, $count, 'Created ticket should have at least one article');
    }

    /**
     * Fetches articles via TicketRepository::getTicketArticles() shortcut.
     */
    public function testGetForTicketViaRepository(): void
    {
        $count = 0;
        foreach (self::$client->repo(TicketRepository::class)->getTicketArticles(self::$ticketId) as $article) {
            self::assertInstanceOf(TicketArticleDTO::class, $article);
            $count++;
        }

        self::assertGreaterThan(0, $count, 'Created ticket should have articles via Repository');
    }

    /**
     * Streams all articles globally via the paginated all() generator.
     */
    public function testListAllArticles(): void
    {
        $found = false;
        foreach (self::$client->repo(TicketArticleRepository::class)->all() as $article) {
            $found = true;
            self::assertInstanceOf(TicketArticleDTO::class, $article);
            break;
        }

        self::assertTrue($found, 'Should find at least one article globally');
    }
}
