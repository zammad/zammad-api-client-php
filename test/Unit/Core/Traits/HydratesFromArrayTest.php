<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core\Traits;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Core\Traits\HydratesFromArray;

#[Group('unit')]
final class HydratesFromArrayTest extends TestCase
{
    public function testFromArrayHydratesConstructorProperties(): void
    {
        $dtoClass = new class('', 0) {
            use HydratesFromArray;
            public function __construct(
                public string $name,
                public int $count,
            ) {
            }
        };

        $result = $dtoClass::fromArray(['name' => 'Hello', 'count' => 5]);

        self::assertSame('Hello', $result->name);
        self::assertSame(5, $result->count);
    }

    public function testFromArrayUsesDefaultsForMissingFields(): void
    {
        $dtoClass = new class('default') {
            use HydratesFromArray;
            public function __construct(
                public string $name = 'default',
                public ?int $id = null,
            ) {
            }
        };

        $result = $dtoClass::fromArray(['name' => 'Overridden']);

        self::assertSame('Overridden', $result->name);
        self::assertNull($result->id);
    }

    public function testFromArrayCoercesTypes(): void
    {
        $dtoClass = new class(0) {
            use HydratesFromArray;
            public function __construct(
                public int $count,
            ) {
            }
        };

        $result = $dtoClass::fromArray(['count' => '42']);

        self::assertSame(42, $result->count);
    }
}
