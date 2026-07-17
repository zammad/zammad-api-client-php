<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core\Traits;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Core\Traits\SerializesToArray;

#[Group('unit')]
final class SerializesToArrayTest extends TestCase
{
    public function testToArrayExcludesNullValues(): void
    {
        $dto = new class('Hello') {
            use SerializesToArray;
            public function __construct(
                public string $title = 'Hello',
                public ?int $id = null,
                public ?string $note = null,
            ) {
            }
        };

        $result = $dto->toArray();

        self::assertArrayHasKey('title', $result);
        self::assertSame('Hello', $result['title']);
        self::assertArrayNotHasKey('id', $result);
        self::assertArrayNotHasKey('note', $result);
    }

    public function testToArrayFormatsDateTime(): void
    {
        $date = new DateTimeImmutable('2026-07-20T12:00:00+00:00');
        $dto = new class($date) {
            use SerializesToArray;
            public function __construct(
                public ?DateTimeImmutable $created_at = null,
            ) {
            }
        };

        $result = $dto->toArray();

        self::assertArrayHasKey('created_at', $result);
        self::assertStringContainsString('2026-07-20', $result['created_at']);
    }

    public function testToArrayIncludesMixedFields(): void
    {
        $dto = new class('Tag', 'Normal') {
            use SerializesToArray;
            public function __construct(
                public string $name,
                public string $value,
                public ?int $id = null,
                public bool $active = true,
            ) {
            }
        };

        $result = $dto->toArray();

        self::assertSame('Tag', $result['name']);
        self::assertSame('Normal', $result['value']);
        self::assertTrue($result['active']);
        self::assertArrayNotHasKey('id', $result);
    }

    public function testIdReturnsProperty(): void
    {
        $dto = new class(42) {
            use SerializesToArray;
            public function __construct(
                public readonly ?int $id = null,
            ) {
            }
        };

        self::assertSame(42, $dto->id());
    }

    public function testIdReturnsNullWhenNotSet(): void
    {
        $dto = new class {
            use SerializesToArray;
            public ?int $id = null;
        };

        self::assertNull($dto->id());
    }

    public function testJsonSerializeMatchesToArray(): void
    {
        $dto = new class('Hello') {
            use SerializesToArray;
            public function __construct(
                public string $title = 'Hello',
                public ?int $id = null,
            ) {
            }
        };

        self::assertSame($dto->toArray(), $dto->jsonSerialize());
    }

    public function testCustomFieldsAreFlattened(): void
    {
        $dto = new class {
            use SerializesToArray;
            public ?int $id = null;
            public array $customFields = [];
        };

        $dto->customFields = ['custom_ticket_type' => 'bug', 'preferences' => 'should-be-filtered'];

        $result = $dto->toArray();

        self::assertArrayHasKey('custom_ticket_type', $result);
        self::assertSame('bug', $result['custom_ticket_type']);
        self::assertArrayNotHasKey('preferences', $result, 'Server read-only keys must be filtered');
    }
}
