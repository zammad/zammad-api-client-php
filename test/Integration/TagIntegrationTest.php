<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\Tags\TagRepository;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
use ZammadAPIClient\ZammadClient;

#[Group('integration')]
final class TagIntegrationTest extends TestCase
{
    use \ZammadAPIClient\Tests\Integration\Traits\CreatesZammadClient;

    private static ZammadClient $client;
    private static ?int $ticketId = null;

    /**
     * Creates a test ticket and initializes the client.
     */
    public static function setUpBeforeClass(): void
    {
        self::$client = self::createZammadClient();

        $ticket = self::$client->repo(TicketRepository::class)->create(new TicketDTO(
            title: 'Tag Test ' . uniqid('', true),
            group_id: 1,
            priority_id: 2,
            state_id: 1,
            customer_id: 1,
        ));
        self::$ticketId = $ticket->id;
    }

    /**
     * Adds a tag to the test ticket and verifies the API response.
     */
    public function testAddTag(): void
    {
        $tagName = 'it-' . uniqid('', true);
        $result = self::$client->repo(TagRepository::class)->add('Ticket', self::$ticketId, $tagName);

        self::assertIsArray($result);
    }

    /**
     * Lists all tags attached to the test ticket via the paginated all() generator.
     */
    public function testListTagsForTicket(): void
    {
        $tagName = 'it-list-' . uniqid('', true);
        self::$client->repo(TagRepository::class)->add('Ticket', self::$ticketId, $tagName);

        $found = false;
        foreach (self::$client->repo(TagRepository::class)->all(['object' => 'Ticket', 'o_id' => self::$ticketId]) as $tag) {
            if ($tag->value === $tagName) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found, 'Tag list should include the added tag');
    }

    /**
     * Removes a tag from the test ticket and verifies the API response.
     */
    public function testRemoveTag(): void
    {
        $tagName = 'it-remove-' . uniqid('', true);
        self::$client->repo(TagRepository::class)->add('Ticket', self::$ticketId, $tagName);

        $result = self::$client->repo(TagRepository::class)->remove('Ticket', self::$ticketId, $tagName);

        self::assertIsArray($result);
    }

    /**
     * Searches tags globally via the tagSearch autocomplete endpoint.
     */
    public function testTagSearch(): void
    {
        $tagName = 'it-search-' . uniqid('', true);
        self::$client->repo(TagRepository::class)->add('Ticket', self::$ticketId, $tagName);

        $results = self::$client->repo(TagRepository::class)->tagSearch($tagName);

        self::assertIsArray($results);
    }

    /**
     * Test ticket is cleaned up in setUp() / tearDown().
     */
    public static function tearDownAfterClass(): void
    {
    }
}
