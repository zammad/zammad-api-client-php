<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\Organizations\OrganizationDTO;
use ZammadAPIClient\Endpoints\Organizations\OrganizationRepository;
use ZammadAPIClient\ZammadClient;

#[Group('integration')]
final class OrganizationIntegrationTest extends TestCase
{
    use \ZammadAPIClient\Tests\Integration\Traits\CreatesZammadClient;

    private static ZammadClient $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = self::createZammadClient();
    }

    /**
     * Streams all organizations via the paginated all() generator and verifies each DTO.
     */
    public function testListAllOrganizations(): void
    {
        $count = 0;
        foreach (self::$client->repo(OrganizationRepository::class)->all() as $org) {
            self::assertInstanceOf(OrganizationDTO::class, $org);
            self::assertGreaterThan(0, $org->id);
            $count++;
        }

        self::assertGreaterThan(0, $count, 'Should find at least one organization');
    }

    /**
     * Creates an organization, verifies the returned DTO, and deletes it.
     */
    public function testCreateAndDeleteOrganization(): void
    {
        $name = 'Test Org ' . uniqid('', true);
        $org = self::$client->repo(OrganizationRepository::class)->create(new OrganizationDTO(name: $name));

        self::assertGreaterThan(0, $org->id);
        self::assertSame($name, $org->name);

        self::$client->repo(OrganizationRepository::class)->delete($org->id);

        self::assertTrue(true);
    }

    /**
     * Fetches the default organization (ID 1) and verifies its name.
     */
    public function testFindOrganization(): void
    {
        $org = self::$client->repo(OrganizationRepository::class)->find(1);

        self::assertNotNull($org->id);
        self::assertNotEmpty($org->name);
    }
}
