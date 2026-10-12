<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Repositories;

use BadMethodCallException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\Roles\RoleDTO;
use ZammadAPIClient\Endpoints\Roles\RoleRepository;

#[Group('unit')]
final class RoleRepositoryTest extends MockeryTestCase
{
    public function testAllPaginatesRoles(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('roles', ['page' => '1', 'per_page' => '2'])
            ->andReturn(['roles' => [
                ['id' => 1, 'name' => 'Admin'],
                ['id' => 2, 'name' => 'Agent'],
            ]]);
        $handler->shouldReceive('get')
            ->once()
            ->with('roles', ['page' => '2', 'per_page' => '2'])
            ->andReturn([]);

        $repo = new RoleRepository($handler, 'roles', RoleDTO::class, 2);
        $roles = iterator_to_array($repo->all());

        self::assertCount(2, $roles);
        self::assertContainsOnlyInstancesOf(RoleDTO::class, $roles);
        self::assertSame('Admin', $roles[0]->name);
        self::assertSame('Agent', $roles[1]->name);
    }

    public function testFindReturnsRoleDto(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('roles/3', ['expand' => 'true'])
            ->andReturn([
                'id' => 3,
                'name' => 'Customer',
                'note' => 'Regular user',
                'active' => true,
                'default_at_signup' => true,
                'permission_ids' => [10, 11],
                'permissions' => ['ticket.customer'],
            ]);

        $repo = new RoleRepository($handler, 'roles', RoleDTO::class);
        $role = $repo->find(3);

        self::assertSame(3, $role->id);
        self::assertSame('Customer', $role->name);
        self::assertSame('Regular user', $role->note);
        self::assertTrue($role->active);
        self::assertTrue($role->default_at_signup);
        self::assertSame([10, 11], $role->permission_ids);
    }

    /**
     * Zammad returns group access on a role as a map of group ID to access
     * level, not as a flat list of IDs.
     */
    public function testFindHydratesGroupAccessMap(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('roles/2', ['expand' => 'true'])
            ->andReturn([
                'id' => 2,
                'name' => 'Agent',
                'group_ids' => ['1' => 'full', '42' => ['read', 'change']],
            ]);

        $repo = new RoleRepository($handler, 'roles', RoleDTO::class);
        $role = $repo->find(2);

        self::assertSame(['1' => 'full', '42' => ['read', 'change']], $role->group_ids);
    }

    /**
     * A customer token gets a reduced role: Zammad masks the name and omits
     * note and default_at_signup. Hydration must still succeed.
     */
    public function testFindHydratesReducedCustomerView(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('roles/3', ['expand' => 'true'])
            ->andReturn([
                'id' => 3,
                'name' => 'Role_3',
                'active' => true,
                'group_ids' => [],
                'permission_ids' => [10],
            ]);

        $repo = new RoleRepository($handler, 'roles', RoleDTO::class);
        $role = $repo->find(3);

        self::assertSame('Role_3', $role->name);
        self::assertNull($role->note);
        self::assertNull($role->default_at_signup);
    }

    public function testCreatePostsAndReturnsDto(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('post')
            ->once()
            ->with('roles', [
                'name' => 'Supervisor',
                'note' => 'Team lead',
                'active' => true,
                'permission_ids' => [10, 11],
            ])
            ->andReturn([
                'id' => 42,
                'name' => 'Supervisor',
                'note' => 'Team lead',
                'active' => true,
                'permission_ids' => [10, 11],
            ]);

        $repo = new RoleRepository($handler, 'roles', RoleDTO::class);
        $role = $repo->create(new RoleDTO(
            name: 'Supervisor',
            note: 'Team lead',
            active: true,
            permission_ids: [10, 11],
        ));

        self::assertSame(42, $role->id);
        self::assertSame('Supervisor', $role->name);
        self::assertSame([10, 11], $role->permission_ids);
    }

    /**
     * Unset fields must stay out of the payload so create() does not clear
     * permissions or group access that the caller never touched.
     */
    public function testCreateOmitsUnsetFields(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('post')
            ->once()
            ->with('roles', ['name' => 'Minimal'])
            ->andReturn(['id' => 43, 'name' => 'Minimal']);

        $repo = new RoleRepository($handler, 'roles', RoleDTO::class);
        $role = $repo->create(new RoleDTO(name: 'Minimal'));

        self::assertSame(43, $role->id);
        self::assertNull($role->permission_ids);
        self::assertNull($role->group_ids);
    }

    public function testPatchSendsOnlyChangedFields(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('put')
            ->once()
            ->with('roles/42', ['active' => false])
            ->andReturn(['id' => 42, 'name' => 'Supervisor', 'active' => false]);

        $repo = new RoleRepository($handler, 'roles', RoleDTO::class);
        $role = $repo->patch(42, ['active' => false]);

        self::assertSame(42, $role->id);
        self::assertFalse($role->active);
    }

    /**
     * Roles cannot be removed through the Zammad API, so RoleRepository does not
     * implement DeletableInterface — the inherited delete() must throw instead
     * of silently issuing a request.
     */
    public function testDeleteThrowsBecauseRolesAreNotDeletable(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldNotReceive('delete');

        $repo = new RoleRepository($handler, 'roles', RoleDTO::class);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('does not support delete()');

        $repo->delete(1);
    }

    /**
     * `/roles/search` answers with a bare JSON array rather than a `roles`
     * envelope, so the generator path has to fall back to the whole body.
     */
    public function testSearchUsesSearchEndpoint(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('roles/search', ['query' => 'Agent', 'page' => '1', 'per_page' => '1'])
            ->andReturn([['id' => 2, 'name' => 'Agent']]);
        $handler->shouldReceive('get')
            ->once()
            ->with('roles/search', ['query' => 'Agent', 'page' => '2', 'per_page' => '1'])
            ->andReturn([]);

        $repo = new RoleRepository($handler, 'roles', RoleDTO::class, 1);
        $roles = iterator_to_array($repo->search('Agent'));

        self::assertCount(1, $roles);
        self::assertSame('Agent', $roles[0]->name);
    }
}
