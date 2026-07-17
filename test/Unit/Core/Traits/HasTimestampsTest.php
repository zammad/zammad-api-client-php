<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core\Traits;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Core\Traits\HasTimestamps;

#[Group('unit')]
final class HasTimestampsTest extends TestCase
{
    public function testCreatedAtReturnsNullWhenNotSet(): void
    {
        $dto = new /**
            * @property ?DateTimeImmutable $created_at
            * @property ?DateTimeImmutable $updated_at
            */ class {
            use HasTimestamps;
            public ?DateTimeImmutable $created_at = null;
            public ?DateTimeImmutable $updated_at = null;
        };

        self::assertNull($dto->createdAt());
        self::assertNull($dto->updatedAt());
    }

    public function testCreatedAtReturnsDateTimeWhenSet(): void
    {
        $dto = new class {
            use HasTimestamps;
            public ?DateTimeImmutable $created_at = null;
            public ?DateTimeImmutable $updated_at = null;
        };

        $now = new DateTimeImmutable('2026-07-20T12:00:00+00:00');
        $dto->created_at = $now;
        $dto->updated_at = $now;

        self::assertSame($now, $dto->createdAt());
        self::assertSame($now, $dto->updatedAt());
    }
}
