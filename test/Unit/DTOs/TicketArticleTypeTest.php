<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\DTOs;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleType;

#[Group('unit')]
final class TicketArticleTypeTest extends TestCase
{
    public function testEnumCasesHaveCorrectValues(): void
    {
        self::assertSame('note', TicketArticleType::Note->value);
        self::assertSame('email', TicketArticleType::Email->value);
        self::assertSame('phone', TicketArticleType::Phone->value);
        self::assertSame('sms', TicketArticleType::Sms->value);
        self::assertSame('web', TicketArticleType::Web->value);
    }

    public function testFromStringRoundtrips(): void
    {
        self::assertSame(TicketArticleType::Note, TicketArticleType::from('note'));
        self::assertSame(TicketArticleType::Email, TicketArticleType::from('email'));
        self::assertSame(TicketArticleType::Phone, TicketArticleType::from('phone'));
        self::assertSame(TicketArticleType::Sms, TicketArticleType::from('sms'));
        self::assertSame(TicketArticleType::Web, TicketArticleType::from('web'));
    }

    public function testTryFromReturnsNullForUnknownType(): void
    {
        self::assertNull(TicketArticleType::tryFrom('unknown'));
    }

    public function testAllCasesCoverExpectedValues(): void
    {
        $cases = array_map(fn(TicketArticleType $t): string => $t->value, TicketArticleType::cases());

        self::assertContains('note', $cases);
        self::assertContains('email', $cases);
        self::assertContains('phone', $cases);
        self::assertContains('sms', $cases);
        self::assertContains('web', $cases);
    }
}
