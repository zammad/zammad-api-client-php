<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\Groups\GroupDTO;
use ZammadAPIClient\Endpoints\Groups\GroupRepository;
use ZammadAPIClient\ZammadClient;

#[Group('integration')]
final class GroupIntegrationTest extends TestCase
{
    use \ZammadAPIClient\Tests\Integration\Traits\CreatesZammadClient;

    private static ZammadClient $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = self::createZammadClient();
    }

    /**
     * Streams all groups via the paginated all() generator and verifies each DTO.
     */
    public function testListAllGroups(): void
    {
        $count = 0;
        foreach (self::$client->repo(GroupRepository::class)->all() as $group) {
            self::assertInstanceOf(GroupDTO::class, $group);
            self::assertGreaterThan(0, $group->id);
            $count++;
        }

        self::assertGreaterThan(0, $count, 'Should find at least one group');
    }

    /**
     * Fetches the default group (ID 1) and verifies its name.
     */
    public function testFindGroup(): void
    {
        $group = self::$client->repo(GroupRepository::class)->find(1);

        self::assertNotNull($group->id);
        self::assertNotEmpty($group->name);
    }

    /**
     * Creates a group, verifies the returned DTO, and deletes it.
     */
    public function testCreateAndDeleteGroup(): void
    {
        $name = 'Test Group ' . uniqid('', true);
        $group = self::$client->repo(GroupRepository::class)->create(new GroupDTO(name: $name));

        self::assertGreaterThan(0, $group->id);
        self::assertSame($name, $group->name);

        self::$client->repo(GroupRepository::class)->delete($group->id);

        self::assertTrue(true);
    }
}
