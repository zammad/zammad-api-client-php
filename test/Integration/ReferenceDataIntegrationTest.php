<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\TextModules\TextModuleDTO;
use ZammadAPIClient\Endpoints\TextModules\TextModuleRepository;
use ZammadAPIClient\Endpoints\TicketPriorities\TicketPriorityDTO;
use ZammadAPIClient\Endpoints\TicketPriorities\TicketPriorityRepository;
use ZammadAPIClient\Endpoints\TicketStates\TicketStateDTO;
use ZammadAPIClient\Endpoints\TicketStates\TicketStateRepository;
use ZammadAPIClient\ZammadClient;

#[Group('integration')]
final class ReferenceDataIntegrationTest extends TestCase
{
    use \ZammadAPIClient\Tests\Integration\Traits\CreatesZammadClient;

    private static ZammadClient $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = self::createZammadClient();
    }

    /**
     * Lists all ticket states via the paginated all() generator.
     */
    public function testListAllTicketStates(): void
    {
        $count = 0;
        foreach (self::$client->repo(TicketStateRepository::class)->all() as $state) {
            self::assertInstanceOf(TicketStateDTO::class, $state);
            self::assertGreaterThan(0, $state->id);
            $count++;
        }

        self::assertGreaterThan(0, $count, 'Should find at least one ticket state');
    }

    /**
     * Lists all ticket priorities via the paginated all() generator.
     */
    public function testListAllTicketPriorities(): void
    {
        $count = 0;
        foreach (self::$client->repo(TicketPriorityRepository::class)->all() as $priority) {
            self::assertInstanceOf(TicketPriorityDTO::class, $priority);
            self::assertGreaterThan(0, $priority->id);
            $count++;
        }

        self::assertGreaterThan(0, $count, 'Should find at least one ticket priority');
    }

    /**
     * Creates a text module, verifies it can be found, then deletes it.
     */
    public function testCreateFindDeleteTextModule(): void
    {
        $name = 'IT-Test ' . uniqid('', true);
        $module = self::$client->repo(TextModuleRepository::class)->create(new TextModuleDTO(
            name: $name,
            keywords: 'test, integration',
            content: 'Hello #{customer.firstname}',
        ));

        self::assertGreaterThan(0, $module->id);
        self::assertSame($name, $module->name);

        $found = self::$client->repo(TextModuleRepository::class)->find($module->id);
        self::assertSame($module->id, $found->id);

        self::$client->repo(TextModuleRepository::class)->delete($module->id);

        self::assertTrue(true);
    }
}
