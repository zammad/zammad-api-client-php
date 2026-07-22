<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZammadAPIClient\Core\Traits\HydratesFromArray;
use ZammadAPIClient\Endpoints\TicketStates\TicketStateDTO;
use ZammadAPIClient\Endpoints\Users\UserDTO;

#[Group('unit')]
final class DtoHydratorTest extends TestCase
{
    public function testReflectionHydratorMapsTypedFields(): void
    {
        $user = UserDTO::fromArray([
            'id' => 7,
            'login' => 'jdoe',
            'email' => 'jdoe@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'phone' => null,
            'organization_ids' => [3],
            'role_ids' => [2],
            'active' => true,
            'created_at' => '2024-01-02T03:04:05Z',
            'updated_at' => '2024-02-03T04:05:06Z',
        ]);

        self::assertSame(7, $user->id);
        self::assertSame('jdoe', $user->login);
        self::assertNull($user->phone);
        self::assertSame([3], $user->organization_ids);
        self::assertSame([2], $user->role_ids);
        self::assertTrue($user->active);
        self::assertInstanceOf(DateTimeImmutable::class, $user->created_at);
        self::assertSame('2024-01-02', $user->created_at->format('Y-m-d'));
    }

    public function testScalarValuesAreCoercedToDeclaredType(): void
    {
        $user = UserDTO::fromArray([
            'id' => '7',
            'organization_ids' => [3],
            'active' => 1,
        ]);

        self::assertSame(7, $user->id);
        self::assertSame([3], $user->organization_ids);
        self::assertTrue($user->active);
    }

    public function testMissingFieldsBecomeNullAndInvalidDateIsLenient(): void
    {
        $user = UserDTO::fromArray([
            'created_at' => 'not-a-date',
        ]);

        self::assertNull($user->id);
        self::assertNull($user->login);
        self::assertNull($user->active);
        self::assertNull($user->created_at);
    }

    public function testNonNullableStringDefaultsToEmptyString(): void
    {
        $state = TicketStateDTO::fromArray([
            'id' => 1,
        ]);

        self::assertSame('', $state->name);
        self::assertNull($state->note);
        self::assertNull($state->active);
    }

    public function testOrganizationIdAndIdsAreIndependent(): void
    {
        $user = UserDTO::fromArray([
            'organization_id'  => 5,
            'organization_ids' => [1, 2],
        ]);

        self::assertSame(5, $user->organization_id);
        self::assertSame([1, 2], $user->organization_ids);
    }

    public function testOrganizationIdIsNullWhenMissing(): void
    {
        $user = UserDTO::fromArray([
            'organization_ids' => [3],
        ]);

        self::assertNull($user->organization_id);
        self::assertSame([3], $user->organization_ids);
    }

    public function testRequireIntThrowsWhenFieldMissing(): void
    {
        $dtoClass = new class(0) {
            use HydratesFromArray;
            public function __construct(
                public int $count,
            ) {
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required field "count" is missing from API response data.');

        $dtoClass::fromArray([]);
    }

    public function testRequireIntThrowsWhenValueNotScalar(): void
    {
        $dtoClass = new class(0) {
            use HydratesFromArray;
            public function __construct(
                public int $count,
            ) {
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required field "count" must be scalar, got array.');

        $dtoClass::fromArray(['count' => ['nested']]);
    }

    public function testRequireBoolReturnsFalseWhenFieldMissing(): void
    {
        $dtoClass = new class(false) {
            use HydratesFromArray;
            public function __construct(
                public bool $active,
            ) {
            }
        };

        $result = $dtoClass::fromArray([]);

        self::assertFalse($result->active);
    }

    public function testRequireBoolCoercesToBool(): void
    {
        $dtoClass = new class(false) {
            use HydratesFromArray;
            public function __construct(
                public bool $active,
            ) {
            }
        };

        $result = $dtoClass::fromArray(['active' => '1']);

        self::assertTrue($result->active);
    }
}
