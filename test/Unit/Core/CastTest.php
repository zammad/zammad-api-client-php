<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Core\Cast;

#[Group('unit')]
final class CastTest extends TestCase
{
    public function testDateTimeParsesIso8601(): void
    {
        $result = Cast::dateTime(['created_at' => '2024-01-15T10:30:00Z'], 'created_at');

        self::assertNotNull($result);
        self::assertSame('2024-01-15T10:30:00+00:00', $result->format('c'));
    }

    public function testDateTimeReturnsNullForMissingKey(): void
    {
        self::assertNull(Cast::dateTime([], 'created_at'));
    }

    public function testDateTimeReturnsNullForEmptyString(): void
    {
        self::assertNull(Cast::dateTime(['created_at' => ''], 'created_at'));
    }

    public function testDateTimeReturnsNullForInvalidFormat(): void
    {
        self::assertNull(Cast::dateTime(['created_at' => 'not-a-date'], 'created_at'));
    }

    public function testStringReturnsValue(): void
    {
        self::assertSame('hello', Cast::string(['name' => 'hello'], 'name'));
    }

    public function testStringReturnsDefaultWhenMissing(): void
    {
        self::assertSame('', Cast::string([], 'name'));
    }

    public function testStringReturnsCustomDefault(): void
    {
        self::assertSame('fallback', Cast::string([], 'name', 'fallback'));
    }

    public function testStringReturnsDefaultForNonStringValue(): void
    {
        self::assertSame('', Cast::string(['name' => 42], 'name'));
    }

    public function testStringOrNullReturnsValue(): void
    {
        self::assertSame('hello', Cast::stringOrNull(['name' => 'hello'], 'name'));
    }

    public function testStringOrNullReturnsNullWhenMissing(): void
    {
        self::assertNull(Cast::stringOrNull([], 'name'));
    }

    public function testStringOrNullReturnsNullForNonString(): void
    {
        self::assertNull(Cast::stringOrNull(['name' => ['array']], 'name'));
    }

    public function testIntOrNullReturnsValue(): void
    {
        self::assertSame(42, Cast::intOrNull(['id' => 42], 'id'));
    }

    public function testIntOrNullCastsNumericString(): void
    {
        self::assertSame(42, Cast::intOrNull(['id' => '42'], 'id'));
    }

    public function testIntOrNullReturnsNullWhenMissing(): void
    {
        self::assertNull(Cast::intOrNull([], 'id'));
    }

    public function testIntOrNullReturnsNullForArray(): void
    {
        self::assertNull(Cast::intOrNull(['id' => []], 'id'));
    }

    public function testBoolOrNullReturnsTrue(): void
    {
        self::assertTrue(Cast::boolOrNull(['active' => true], 'active'));
    }

    public function testBoolOrNullReturnsFalse(): void
    {
        self::assertFalse(Cast::boolOrNull(['active' => false], 'active'));
    }

    public function testBoolOrNullCastsZeroToFalse(): void
    {
        self::assertFalse(Cast::boolOrNull(['active' => 0], 'active'));
    }

    public function testBoolOrNullCastsOneToTrue(): void
    {
        self::assertTrue(Cast::boolOrNull(['active' => 1], 'active'));
    }

    public function testBoolOrNullReturnsNullWhenMissing(): void
    {
        self::assertNull(Cast::boolOrNull([], 'active'));
    }
}
