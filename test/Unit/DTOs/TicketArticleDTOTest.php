<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\DTOs;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleDTO;

#[Group('unit')]
final class TicketArticleDTOTest extends TestCase
{
    public function testFromArrayHydratesAllFields(): void
    {
        $dto = TicketArticleDTO::fromArray([
            'id'             => 42,
            'ticket_id'      => 7,
            'type'           => 'email',
            'body'           => 'Hello world',
            'content_type'   => 'text/html',
            'subject'        => 'Re: Issue',
            'from'           => 'agent@example.com',
            'to'             => 'customer@example.com',
            'cc'             => 'manager@example.com',
            'internal'       => false,
            'in_reply_to'    => '<msg-abc@example.com>',
            'reply_to'       => 'support@example.com',
            'message_id'     => '<msg-xyz@example.com>',
            'origin_by_id'   => 3,
            'sender'         => 'Agent',
            'type_id'        => 9,
            'sender_id'      => 2,
            'created_by_id'  => 5,
            'updated_by_id'  => 6,
            'created_by'     => 'admin@example.com',
            'updated_by'     => 'agent@example.com',
            'time_unit'      => 5.5,
            'created_at'     => '2026-01-01T12:00:00Z',
            'updated_at'     => '2026-01-02T14:30:00Z',
        ]);

        self::assertSame(42, $dto->id);
        self::assertSame(7, $dto->ticket_id);
        self::assertSame('email', $dto->type);
        self::assertSame('Agent', $dto->sender);
        self::assertSame(9, $dto->type_id);
        self::assertSame(2, $dto->sender_id);
        self::assertSame(5, $dto->created_by_id);
        self::assertSame(6, $dto->updated_by_id);
        self::assertSame('admin@example.com', $dto->created_by);
        self::assertSame('agent@example.com', $dto->updated_by);
        self::assertSame('support@example.com', $dto->reply_to);
        self::assertSame('<msg-xyz@example.com>', $dto->message_id);
        self::assertSame(5.5, $dto->time_unit);
    }

    public function testFromArrayWithInternalNote(): void
    {
        $dto = TicketArticleDTO::fromArray([
            'ticket_id' => 1,
            'type'      => 'note',
            'body'      => 'Internal remark',
            'internal'  => true,
        ]);

        self::assertTrue($dto->internal);
        self::assertSame('note', $dto->type);
    }

    public function testAttachmentsIsIncludedWhenSet(): void
    {
        $dto = new TicketArticleDTO(
            ticket_id: 1,
            attachments: [
                ['filename' => 'test.txt', 'data' => base64_encode('hello'), 'mime-type' => 'text/plain'],
            ],
        );

        $array = $dto->toArray();

        self::assertArrayHasKey('attachments', $array);
        self::assertCount(1, $array['attachments']);
        self::assertSame('test.txt', $array['attachments'][0]['filename']);
    }

    public function testAttachmentsIsExcludedWhenNull(): void
    {
        $dto = new TicketArticleDTO(ticket_id: 1);

        $array = $dto->toArray();

        self::assertArrayNotHasKey('attachments', $array);
    }

    public function testToArrayIncludesAllFields(): void
    {
        $dto = TicketArticleDTO::fromArray([
            'ticket_id'    => 5,
            'type'         => 'email',
            'body'         => 'Body',
            'content_type' => 'text/plain',
            'subject'      => 'S',
            'from'         => 'a@b.com',
        ]);

        $array = $dto->toArray();

        self::assertArrayHasKey('ticket_id', $array);
        self::assertArrayHasKey('type', $array);
        self::assertArrayHasKey('body', $array);
        self::assertArrayHasKey('content_type', $array);
        self::assertArrayHasKey('subject', $array);
        self::assertArrayHasKey('from', $array);
    }

    public function testCreateArticleWithNewFields(): void
    {
        $dto = new TicketArticleDTO(
            ticket_id: 10,
            type: 'email',
            body: 'Response text',
            content_type: 'text/html',
            subject: 'Ticket update',
            from: 'support@example.com',
            to: 'client@example.com',
            cc: 'archive@example.com',
            internal: false,
            in_reply_to: '<msg-xyz@example.com>',
            origin_by_id: 5,
        );

        $array = $dto->toArray();

        self::assertSame(10, $array['ticket_id']);
        self::assertSame('email', $array['type']);
        self::assertSame('Response text', $array['body']);
        self::assertSame('text/html', $array['content_type']);
        self::assertSame('Ticket update', $array['subject']);
        self::assertSame('support@example.com', $array['from']);
        self::assertSame('client@example.com', $array['to']);
        self::assertSame('archive@example.com', $array['cc']);
        self::assertFalse($array['internal']);
        self::assertSame('<msg-xyz@example.com>', $array['in_reply_to']);
        self::assertSame(5, $array['origin_by_id']);
    }
}
