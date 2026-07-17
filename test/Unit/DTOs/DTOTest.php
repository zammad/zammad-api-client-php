<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\DTOs;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\Groups\GroupDTO;
use ZammadAPIClient\Endpoints\Links\LinkDTO;
use ZammadAPIClient\Endpoints\Organizations\OrganizationDTO;
use ZammadAPIClient\Endpoints\Tags\TagDTO;
use ZammadAPIClient\Endpoints\TextModules\TextModuleDTO;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleDTO;
use ZammadAPIClient\Endpoints\TicketPriorities\TicketPriorityDTO;
use ZammadAPIClient\Endpoints\TicketStates\TicketStateDTO;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Endpoints\Users\UserDTO;

#[Group('unit')]
final class DTOTest extends TestCase
{
    /** @return array<string, array{class-string, array<string, mixed>}> */
    public static function dtoProvider(): array
    {
        return [
            'GroupDTO' => [
                GroupDTO::class,
                ['id' => 1, 'name' => 'Users', 'note' => 'Default group', 'active' => true],
            ],
            'LinkDTO' => [
                LinkDTO::class,
                ['id' => 1, 'link_type' => 'normal', 'link_object_source' => 'Ticket', 'link_object_source_value' => 1],
            ],
            'OrganizationDTO' => [
                OrganizationDTO::class,
                ['id' => 1, 'name' => 'Zammad GmbH', 'active' => true, 'note' => 'Our company'],
            ],
            'TagDTO' => [
                TagDTO::class,
                ['id' => 1, 'object' => 'Ticket', 'o_id' => 42, 'value' => 'bug'],
            ],
            'TextModuleDTO' => [
                TextModuleDTO::class,
                ['id' => 1, 'name' => 'Greeting', 'keywords' => 'hello, hi', 'content' => 'Hello!', 'active' => true],
            ],
            'TicketArticleDTO' => [
                TicketArticleDTO::class,
                ['id' => 1, 'ticket_id' => 42, 'type' => 'note', 'subject' => 'Update', 'body' => 'Fixed it'],
            ],
            'TicketPriorityDTO' => [
                TicketPriorityDTO::class,
                ['id' => 1, 'name' => '3 high', 'active' => true, 'note' => 'Critical issues'],
            ],
            'TicketStateDTO' => [
                TicketStateDTO::class,
                ['id' => 1, 'name' => 'open', 'active' => true, 'note' => 'New tickets'],
            ],
            'UserDTO' => [
                UserDTO::class,
                ['id' => 1, 'login' => 'agent', 'email' => 'agent@example.com', 'firstname' => 'John', 'active' => true],
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    #[DataProvider('dtoProvider')]
    public function testFromArrayCreatesDto(string $dtoClass, array $data): void
    {
        $dto = $dtoClass::fromArray($data);

        self::assertInstanceOf($dtoClass, $dto);
        self::assertSame($data['id'], $dto->id);
    }

    /** @param array<string, mixed> $data */
    #[DataProvider('dtoProvider')]
    public function testToArrayReturnsArray(string $dtoClass, array $data): void
    {
        $dto = $dtoClass::fromArray($data);
        $result = $dto->toArray();

        self::assertIsArray($result);
        self::assertArrayHasKey('id', $result);
        self::assertSame($data['id'], $result['id']);
    }

    /** @param array<string, mixed> $data */
    #[DataProvider('dtoProvider')]
    public function testJsonSerializeMatchesToArray(string $dtoClass, array $data): void
    {
        $dto = $dtoClass::fromArray($data);

        self::assertSame($dto->toArray(), $dto->jsonSerialize());
    }

    /** @param array<string, mixed> $data */
    #[DataProvider('dtoProvider')]
    public function testMissingFieldsDefaultToNull(string $dtoClass, array $data): void
    {
        $dto = $dtoClass::fromArray(['id' => 1]);

        self::assertSame(1, $dto->id);
    }

    /** @param array<string, mixed> $data */
    #[DataProvider('dtoProvider')]
    public function testExtraFieldsAreIgnored(string $dtoClass, array $data): void
    {
        $withExtra = array_merge($data, ['unknown_field' => 'should be ignored']);
        $dto = $dtoClass::fromArray($withExtra);

        self::assertInstanceOf($dtoClass, $dto);
    }

    /** @param array<string, mixed> $data */
    #[DataProvider('dtoProvider')]
    public function testEmptyArrayCreatesDto(string $dtoClass, array $data): void
    {
        $dto = $dtoClass::fromArray([]);

        self::assertInstanceOf($dtoClass, $dto);
        self::assertNull($dto->id);
    }

    public function testGroupDtoCreatedAtAndUpdatedAtAreNullWhenNotProvided(): void
    {
        $dto = GroupDTO::fromArray(['name' => 'Test']);

        self::assertNull($dto->createdAt());
        self::assertNull($dto->updatedAt());
    }

    public function testUserDtoCreatedAtReturnsDateTimeWhenProvided(): void
    {
        $dto = UserDTO::fromArray(['login' => 'test', 'created_at' => '2024-01-15T10:30:00Z']);

        self::assertNotNull($dto->createdAt());
        self::assertSame('2024-01-15T10:30:00+00:00', $dto->createdAt()?->format('c'));
    }

    public function testTagDtoHasNoTimestamps(): void
    {
        $dto = TagDTO::fromArray(['id' => 1, 'value' => 'test']);

        self::assertSame(1, $dto->id);
        self::assertSame('test', $dto->value);
    }

    public function testCustomFieldsAreHydratedFromApiResponse(): void
    {
        $ticket = TicketDTO::fromArray([
            'title'            => 'Test',
            'custom_field_abc' => 'value1',
            'custom_field_xyz' => 42,
        ]);

        self::assertSame('value1', $ticket->customFields['custom_field_abc']);
        self::assertSame(42, $ticket->customFields['custom_field_xyz']);
    }

    public function testCustomFieldsAreSerialized(): void
    {
        $ticket = TicketDTO::fromArray([
            'title'            => 'Test',
            'custom_field_abc' => 'value1',
        ]);

        $array = $ticket->toArray();

        self::assertArrayHasKey('title', $array);
        self::assertArrayHasKey('custom_field_abc', $array);
        self::assertSame('value1', $array['custom_field_abc']);
    }

    public function testCustomFieldsIncludeReadOnlyServerFields(): void
    {
        $ticket = TicketDTO::fromArray([
            'title'       => 'Test',
            'custom_note' => 'Server-set value',
            'created_at'  => '2024-01-15T10:30:00Z',
        ]);

        self::assertArrayNotHasKey('created_at', $ticket->customFields);
        self::assertSame('Server-set value', $ticket->customFields['custom_note']);
    }

    public function testDtoWithoutCustomFieldsIgnoresUnknownKeys(): void
    {
        $tag = TagDTO::fromArray([
            'id'            => 1,
            'object'        => 'Ticket',
            'o_id'          => 42,
            'value'         => 'bug',
            'unknown_field' => 'should be ignored',
        ]);

        self::assertSame(1, $tag->id);
        self::assertSame('bug', $tag->value);
    }

    public function testCustomFieldsFiltersServerReadOnlyKeys(): void
    {
        $ticket = TicketDTO::fromArray([
            'title'                       => 'Test',
            'custom_note'                 => 'Persisted custom value',
            'article_count'               => 5,
            'preferences'                 => ['locale' => 'de'],
            'created_by_id'               => 3,
            'updated_by_id'               => 4,
            'create_article_type_id'      => 1,
            'checklist_id'                => 42,
            'pending_reminder_at'         => '2026-01-01T00:00:00Z',
            'referencing_checklist_ids'   => [1, 2],
            'ticket_time_accounting_ids'  => [99],
            'last_owner_update_at'        => '2026-01-01T00:00:00Z',
        ]);

        $result = $ticket->toArray();

        self::assertArrayHasKey('custom_note', $result, 'Custom field should be included.');
        self::assertSame('Persisted custom value', $result['custom_note']);
        self::assertArrayNotHasKey('article_count', $result);
        self::assertArrayNotHasKey('preferences', $result);
        self::assertArrayNotHasKey('created_by_id', $result);
        self::assertArrayNotHasKey('updated_by_id', $result);
        self::assertArrayNotHasKey('create_article_type_id', $result);
        self::assertArrayNotHasKey('checklist_id', $result);
        self::assertArrayNotHasKey('pending_reminder_at', $result);
        self::assertArrayNotHasKey('referencing_checklist_ids', $result);
        self::assertArrayNotHasKey('ticket_time_accounting_ids', $result);
        self::assertArrayNotHasKey('last_owner_update_at', $result);
    }
}
