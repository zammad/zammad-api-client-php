<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Endpoints\Roles\RoleDTO;
use ZammadAPIClient\Endpoints\Roles\RoleRepository;
use ZammadAPIClient\Endpoints\Users\UserDTO;
use ZammadAPIClient\Endpoints\Users\UserRepository;
use ZammadAPIClient\ZammadClient;

/**
 * Roles are reference data: these tests read the roles a Zammad installation
 * ships with instead of creating new ones, because the API offers no way to
 * delete a role again.
 *
 * Requires a token with admin permissions — `/api/v1/roles` is admin-only.
 */
#[Group('integration')]
final class RoleIntegrationTest extends TestCase
{
    use \ZammadAPIClient\Tests\Integration\Traits\CreatesZammadClient;

    private static ZammadClient $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = self::createZammadClient();
    }

    /**
     * Lists all roles via the paginated all() generator.
     */
    public function testListAllRoles(): void
    {
        $count = 0;
        foreach (self::$client->role()->all() as $role) {
            self::assertInstanceOf(RoleDTO::class, $role);
            self::assertGreaterThan(0, $role->id);
            self::assertNotSame('', $role->name);
            $count++;
        }

        self::assertGreaterThan(0, $count, 'Should find at least one role');
    }

    /**
     * A default Zammad installation ships with Admin, Agent and Customer.
     */
    public function testDefaultRolesArePresent(): void
    {
        $names = [];
        foreach (self::$client->role()->all() as $role) {
            $names[] = $role->name;
        }

        self::assertContains('Admin', $names);
        self::assertContains('Agent', $names);
        self::assertContains('Customer', $names);
    }

    /**
     * find() returns the same role that all() yielded.
     */
    public function testFindRole(): void
    {
        $first = null;
        foreach (self::$client->role()->all() as $role) {
            $first = $role;
            break;
        }

        self::assertInstanceOf(RoleDTO::class, $first, 'Should find at least one role');

        $found = self::$client->role()->find($first->id);

        self::assertSame($first->id, $found->id);
        self::assertSame($first->name, $found->name);
    }

    /**
     * The motivating use case of the roles endpoint: resolve a role name to its
     * ID and use it as `role_ids` when creating a user.
     */
    public function testCreateUserWithResolvedRoleId(): void
    {
        $customerRoleId = null;
        foreach (self::$client->role()->all() as $role) {
            if ($role->name === 'Customer') {
                $customerRoleId = $role->id;
                break;
            }
        }

        self::assertNotNull($customerRoleId, 'Customer role should exist');

        $email = 'role-it-' . uniqid('', true) . '@example.com';
        $user = self::$client->repo(UserRepository::class)->create(new UserDTO(
            email: $email,
            firstname: 'Role',
            lastname: 'Test',
            role_ids: [$customerRoleId],
        ));

        try {
            self::assertGreaterThan(0, $user->id);
            self::assertIsArray($user->role_ids);
            self::assertContains($customerRoleId, $user->role_ids);
        } finally {
            self::$client->repo(UserRepository::class)->delete($user->id);
        }
    }

    /**
     * Roles are returned under the "roles" list key and paginate like any other
     * resource — a page size of 1 must still yield every role exactly once.
     */
    public function testPaginationWithSmallPageSize(): void
    {
        $repo = new RoleRepository(
            self::$client->getHandler(),
            'roles',
            RoleDTO::class,
            1,
        );

        $ids = [];
        foreach ($repo->all() as $role) {
            $ids[] = $role->id;
        }

        self::assertGreaterThan(0, count($ids));
        self::assertSame(array_unique($ids), $ids, 'Pagination must not repeat roles');
    }
}
