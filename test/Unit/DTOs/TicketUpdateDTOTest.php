<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\DTOs;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\Tickets\TicketUpdateDTO;

#[Group('unit')]
final class TicketUpdateDTOTest extends TestCase
{
    public function testToPatchArrayReturnsOnlyNonNullFields(): void
    {
        $dto = new TicketUpdateDTO(title: 'New title');

        $result = $dto->toPatchArray();

        self::assertCount(1, $result);
        self::assertArrayHasKey('title', $result);
        self::assertArrayNotHasKey('state_id', $result);
        self::assertSame('New title', $result['title']);
    }

    public function testToPatchArrayReturnsMultipleFields(): void
    {
        $dto = new TicketUpdateDTO(title: 'New', state_id: 3, group_id: 1);

        $result = $dto->toPatchArray();

        self::assertCount(3, $result);
        self::assertSame('New', $result['title']);
        self::assertSame(3, $result['state_id']);
        self::assertSame(1, $result['group_id']);
    }

    public function testToPatchArrayExcludesNullFields(): void
    {
        $dto = new TicketUpdateDTO(title: null, state_id: 3, group_id: null);

        $result = $dto->toPatchArray();

        self::assertCount(1, $result);
        self::assertArrayHasKey('state_id', $result);
        self::assertArrayNotHasKey('title', $result);
        self::assertArrayNotHasKey('group_id', $result);
    }

    public function testAllNullFieldsReturnsEmpty(): void
    {
        $dto = new TicketUpdateDTO();

        $result = $dto->toPatchArray();

        self::assertSame([], $result);
    }

    public function testPendingTimeIsIncludedWhenSet(): void
    {
        $dto = new TicketUpdateDTO(pending_time: '2026-07-20T14:00:00.000Z');

        $result = $dto->toPatchArray();

        self::assertArrayHasKey('pending_time', $result);
        self::assertSame('2026-07-20T14:00:00.000Z', $result['pending_time']);
    }

    public function testPendingTimeIsExcludedWhenNull(): void
    {
        $dto = new TicketUpdateDTO(state_id: 3);

        $result = $dto->toPatchArray();

        self::assertArrayNotHasKey('pending_time', $result);
    }

    public function testCombinedStateAndPendingTime(): void
    {
        $dto = new TicketUpdateDTO(
            state_id: 3,
            pending_time: '2026-07-20T14:00:00.000Z',
        );

        $result = $dto->toPatchArray();

        self::assertSame(3, $result['state_id']);
        self::assertSame('2026-07-20T14:00:00.000Z', $result['pending_time']);
        self::assertArrayNotHasKey('title', $result);
    }
}
