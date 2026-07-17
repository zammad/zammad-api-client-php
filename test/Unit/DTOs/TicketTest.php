<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\DTOs;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;

#[Group('unit')]
final class TicketTest extends TestCase
{
    public function testFromArrayHydratesAllFieldsIncludingTimestamps(): void
    {
        $ticket = TicketDTO::fromArray([
            'id' => 42,
            'group_id' => 1,
            'priority_id' => 2,
            'state_id' => 3,
            'organization_id' => 4,
            'customer_id' => 5,
            'owner_id' => 6,
            'title' => 'Hello',
            'number' => '10042',
            'created_at' => '2024-01-02T03:04:05Z',
            'updated_at' => '2024-02-03T04:05:06Z',
        ]);

        self::assertSame(42, $ticket->id);
        self::assertSame('Hello', $ticket->title);
        self::assertSame('10042', $ticket->number);
        self::assertInstanceOf(DateTimeImmutable::class, $ticket->created_at);
        self::assertInstanceOf(DateTimeImmutable::class, $ticket->updated_at);
        self::assertSame('2024-01-02', $ticket->created_at->format('Y-m-d'));
    }

    public function testOwnerIdFallsBackToAssignedToId(): void
    {
        $ticket = TicketDTO::fromArray([
            'title' => 'x',
            'assigned_to_id' => 99,
        ]);

        self::assertSame(99, $ticket->owner_id);
    }

    public function testMissingFieldsBecomeNullAndInvalidDateIsLenient(): void
    {
        $ticket = TicketDTO::fromArray([
            'title' => 'x',
            'created_at' => 'not-a-date',
        ]);

        self::assertNull($ticket->id);
        self::assertNull($ticket->number);
        self::assertNull($ticket->created_at);
    }

    public function testToArrayOmitsNullsAndFormatsDates(): void
    {
        $ticket = TicketDTO::fromArray([
            'title' => 'x',
            'created_at' => '2024-01-02T03:04:05+00:00',
        ]);

        $array = $ticket->toArray();

        self::assertArrayNotHasKey('id', $array);
        self::assertArrayHasKey('title', $array);
        self::assertSame('2024-01-02T03:04:05+00:00', $array['created_at']);
    }

    public function testPendingTimeIsHydratedFromApiResponse(): void
    {
        $ticket = TicketDTO::fromArray([
            'title'        => 'Test',
            'pending_time' => '2026-07-20T14:00:00.000Z',
        ]);

        self::assertInstanceOf(DateTimeImmutable::class, $ticket->pending_time);
        self::assertSame('2026-07-20T14:00:00+00:00', $ticket->pending_time->format('Y-m-d\TH:i:sP'));
    }

    public function testPendingTimeIsNullWhenMissing(): void
    {
        $ticket = TicketDTO::fromArray(['title' => 'Test']);

        self::assertNull($ticket->pending_time);
    }

    public function testPendingTimeIsSerialized(): void
    {
        $ticket = TicketDTO::fromArray([
            'title'        => 'Test',
            'pending_time' => '2026-07-20T14:00:00.000Z',
        ]);

        $array = $ticket->toArray();

        self::assertArrayHasKey('pending_time', $array);
        self::assertStringContainsString('2026-07-20', $array['pending_time']);
    }

    public function testPendingTimeIsNullForEmptyString(): void
    {
        $ticket = TicketDTO::fromArray([
            'title'        => 'Test',
            'pending_time' => '',
        ]);

        self::assertNull($ticket->pending_time);
    }

    public function testArticleIsHydratedFromApiResponse(): void
    {
        $ticket = TicketDTO::fromArray([
            'title'   => 'Test',
            'article' => ['subject' => 'S', 'body' => 'B', 'type' => 'note'],
        ]);

        self::assertIsArray($ticket->article);
        self::assertSame('S', $ticket->article['subject']);
        self::assertSame('B', $ticket->article['body']);
    }

    public function testArticleIsSerialized(): void
    {
        $ticket = TicketDTO::fromArray([
            'title'   => 'Test',
            'article' => ['subject' => 'S', 'body' => 'B', 'type' => 'note'],
        ]);

        $array = $ticket->toArray();

        self::assertArrayHasKey('article', $array);
        self::assertSame('S', $array['article']['subject']);
    }

    public function testArticleIsExcludedWhenNull(): void
    {
        $ticket = TicketDTO::fromArray(['title' => 'Test']);

        $array = $ticket->toArray();

        self::assertArrayNotHasKey('article', $array);
    }

    public function testOwnerIdTakesPriorityOverAssignedToId(): void
    {
        $ticket = TicketDTO::fromArray([
            'title'          => 'x',
            'owner_id'       => 5,
            'assigned_to_id' => 99,
        ]);

        self::assertSame(5, $ticket->owner_id);
    }
}
