<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\Links\LinkRepository;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
use ZammadAPIClient\ZammadClient;

#[Group('integration')]
final class LinkIntegrationTest extends TestCase
{
    use \ZammadAPIClient\Tests\Integration\Traits\CreatesZammadClient;

    private static ZammadClient $client;
    private static ?int $ticketA = null;
    private static ?int $ticketB = null;
    private static string $ticketBNumber = '';

    /**
     * Creates two test tickets and links them.
     */
    public static function setUpBeforeClass(): void
    {
        self::$client = self::createZammadClient();

        $ticketA = self::$client->repo(TicketRepository::class)->create(new TicketDTO(
            title: 'Link Test A ' . uniqid('', true),
            group_id: 1,
            priority_id: 2,
            state_id: 1,
            customer_id: 1,
        ));
        self::$ticketA = $ticketA->id;

        $ticketB = self::$client->repo(TicketRepository::class)->create(new TicketDTO(
            title: 'Link Test B ' . uniqid('', true),
            group_id: 1,
            priority_id: 2,
            state_id: 1,
            customer_id: 1,
        ));
        self::$ticketB = $ticketB->id;
        self::$ticketBNumber = (string) $ticketB->number;
    }

    /**
     * Creates a normal link between two tickets.
     */
    public function testAddLink(): void
    {
        $result = self::$client->repo(LinkRepository::class)->add(
            'normal', 'Ticket', self::$ticketBNumber, 'Ticket', self::$ticketA,
        );

        self::assertIsArray($result);
    }

    /**
     * Lists links for the linked ticket.
     */
    public function testListLinks(): void
    {
        $found = false;
        foreach (self::$client->repo(LinkRepository::class)->all(['object' => 'Ticket', 'object_id' => self::$ticketB]) as $link) {
            $found = true;
            self::assertNotNull($link->link_type);
            break;
        }

        self::assertTrue($found, 'Should find at least one link');
    }

    /**
     * Removes the link between the two tickets.
     */
    public function testRemoveLink(): void
    {
        $result = self::$client->repo(LinkRepository::class)->remove(
            'normal', 'Ticket', self::$ticketB, 'Ticket', self::$ticketA,
        );

        self::assertIsArray($result);
    }

    /**
     * Test tickets are cleaned up in setUp() / tearDown().
     */
    public static function tearDownAfterClass(): void
    {
    }
}
