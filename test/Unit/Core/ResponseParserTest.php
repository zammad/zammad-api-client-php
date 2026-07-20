<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Core\Repository\ResponseParser;

#[Group('unit')]
final class ResponseParserTest extends TestCase
{
    public function testExtractsItemsFromNamedListKey(): void
    {
        $data = ['tickets' => [['id' => 1], ['id' => 2]], 'assets' => []];

        $result = ResponseParser::extractItems($data, 'tickets');

        self::assertSame([['id' => 1], ['id' => 2]], $result);
    }

    public function testFallsBackToSearchResponseFormatWhenKeyMissing(): void
    {
        $result = ResponseParser::extractItems([['id' => 1], ['id' => 2]], 'tickets');

        self::assertSame([['id' => 1], ['id' => 2]], $result);
    }

    public function testFiltersNonArrayValues(): void
    {
        $data = ['tickets' => [['id' => 1], 'not-an-array', ['id' => 2]]];

        $result = ResponseParser::extractItems($data, 'tickets');

        self::assertSame([['id' => 1], ['id' => 2]], $result);
    }

    public function testReindexesNumericKeys(): void
    {
        $data = ['tickets' => [5 => ['id' => 5], 3 => ['id' => 3]]];

        $result = ResponseParser::extractItems($data, 'tickets');

        self::assertSame([['id' => 5], ['id' => 3]], $result);
    }

    public function testHandlesEmptyNamedArray(): void
    {
        $result = ResponseParser::extractItems(['tickets' => []], 'tickets');

        self::assertSame([], $result);
    }
}
