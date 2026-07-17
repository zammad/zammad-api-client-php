<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleRepository;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
use ZammadAPIClient\Endpoints\Tickets\TicketUpdateDTO;
use ZammadAPIClient\Exceptions\NotFoundException;
use ZammadAPIClient\Exceptions\ValidationException;
use ZammadAPIClient\ZammadClient;

#[Group('integration')]
final class TicketIntegrationTest extends TestCase
{
    use \ZammadAPIClient\Tests\Integration\Traits\CreatesZammadClient;

    private static ZammadClient $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = self::createZammadClient();
    }

    public function testCreateTicket(): void
    {
        $ticket = self::$client->repo(TicketRepository::class)->create(new TicketDTO(
            title: 'Integration Test ' . uniqid('', true),
            group_id: 1,
            priority_id: 2,
            state_id: 1,
            customer_id: 1,
        ));

        self::assertGreaterThan(0, $ticket->id);
        self::assertEquals(1, $ticket->group_id);
        self::assertNotEmpty($ticket->title);
    }

    public function testFindTicket(): void
    {
        $ticket = self::$client->repo(TicketRepository::class)->create(new TicketDTO(
            title: 'Find Test ' . uniqid('', true),
            group_id: 1,
            priority_id: 2,
            state_id: 1,
            customer_id: 1,
        ));

        $found = self::$client->repo(TicketRepository::class)->find($ticket->id);

        self::assertEquals($ticket->id, $found->id);
        self::assertEquals($ticket->title, $found->title);
    }

    public function testPatchTicket(): void
    {
        $ticket = self::$client->repo(TicketRepository::class)->create(new TicketDTO(
            title: 'Patch Test ' . uniqid('', true),
            group_id: 1,
            priority_id: 2,
            state_id: 1,
            customer_id: 1,
        ));

        $newTitle = 'Patched ' . uniqid('', true);
        $patched = self::$client->repo(TicketRepository::class)->patch($ticket->id, ['title' => $newTitle]);

        self::assertEquals($newTitle, $patched->title);
    }

    public function testDeleteTicket(): void
    {
        $ticket = self::$client->repo(TicketRepository::class)->create(new TicketDTO(
            title: 'Delete Test ' . uniqid('', true),
            group_id: 1,
            priority_id: 2,
            state_id: 1,
            customer_id: 1,
            article: [
                'subject' => 'Delete me',
                'body'    => 'Ticket for deletion test',
                'type'    => 'note',
            ],
        ));

        $ticketId = $ticket->id;
        self::assertGreaterThan(0, $ticketId);

        self::$client->repo(TicketRepository::class)->delete($ticketId);

        $this->expectException(NotFoundException::class);
        self::$client->repo(TicketRepository::class)->find($ticketId);
    }

    public function testUpdateTicketWithPendingTime(): void
    {
        $created = self::$client->repo(TicketRepository::class)->create(new TicketDTO(
            title: 'Pending Test ' . uniqid('', true),
            group_id: 1,
            priority_id: 2,
            state_id: 2,
            customer_id: 1,
        ));

        $pendingTime = (new \DateTimeImmutable('+1 hour'))->format('Y-m-d\TH:i:s.000\Z');

        $updated = self::$client->repo(TicketRepository::class)->patch(
            $created->id,
            new TicketUpdateDTO(
                state_id: 3,
                pending_time: $pendingTime,
            ),
        );

        self::assertSame(3, $updated->state_id);
        self::assertNotNull($updated->pending_time);
    }

    public function testUpdateToPendingWithoutTimeFails(): void
    {
        $created = self::$client->repo(TicketRepository::class)->create(new TicketDTO(
            title: 'Pending Fail Test ' . uniqid('', true),
            group_id: 1,
            priority_id: 2,
            state_id: 2,
            customer_id: 1,
        ));

        $this->expectException(ValidationException::class);

        self::$client->repo(TicketRepository::class)->patch(
            $created->id,
            new TicketUpdateDTO(state_id: 3),
        );
    }

    public function testCreateTicketWithArticle(): void
    {
        $created = self::$client->repo(TicketRepository::class)->create(new TicketDTO(
            title: 'Article Test ' . uniqid('', true),
            customer_id: 1,
            group_id: 1,
            state_id: 1,
            priority_id: 2,
            article: [
                'subject' => 'Article Test',
                'body'    => 'Created with article',
                'type'    => 'note',
            ],
        ));

        self::assertGreaterThan(0, $created->id);

        $articles = self::$client->repo(TicketArticleRepository::class)->getForTicket($created->id);
        $found = false;
        foreach ($articles as $article) {
            if (str_contains($article->body ?? '', 'Created with article')) {
                $found = true;
                break;
            }
        }
        self::assertTrue($found, 'The article should exist and contain the expected body');
    }

    public function testCreateTicketWithoutCustomerAndArticleFails(): void
    {
        $this->expectException(ValidationException::class);

        self::$client->repo(TicketRepository::class)->create(new TicketDTO(
            title: 'Should fail ' . uniqid('', true),
            group_id: 1,
            state_id: 1,
            priority_id: 2,
        ));
    }

    public function testCustomFieldsArePreserved(): void
    {
        $created = self::$client->repo(TicketRepository::class)->create(new TicketDTO(
            title: 'Custom Fields ' . uniqid('', true),
            customer_id: 1,
            group_id: 1,
            state_id: 1,
            priority_id: 2,
            article: ['subject' => 'Test', 'body' => 'Test', 'type' => 'note'],
        ));

        self::assertIsArray($created->customFields);

        $fetched = self::$client->repo(TicketRepository::class)->find($created->id);
        self::assertIsArray($fetched->customFields);
    }
}
