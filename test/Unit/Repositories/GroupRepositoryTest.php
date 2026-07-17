<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Repositories;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\Groups\GroupDTO;
use ZammadAPIClient\Endpoints\Groups\GroupRepository;

#[Group('unit')]
final class GroupRepositoryTest extends MockeryTestCase
{
    public function testAllPaginatesGroups(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('groups', ['page' => '1', 'per_page' => '2'])
            ->andReturn(['groups' => [
                ['id' => 1, 'name' => 'Users'],
                ['id' => 2, 'name' => 'Support'],
            ]]);
        $handler->shouldReceive('get')
            ->once()
            ->with('groups', ['page' => '2', 'per_page' => '2'])
            ->andReturn([]);

        $repo = new GroupRepository($handler, 'groups', GroupDTO::class, 2);
        $groups = iterator_to_array($repo->all());

        self::assertCount(2, $groups);
        self::assertContainsOnlyInstancesOf(GroupDTO::class, $groups);
    }

    public function testFindReturnsGroupDto(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('groups/1', ['expand' => 'true'])
            ->andReturn(['id' => 1, 'name' => 'Users']);

        $repo = new GroupRepository($handler, 'groups', GroupDTO::class);
        $group = $repo->find(1);

        self::assertSame(1, $group->id);
        self::assertSame('Users', $group->name);
    }

    public function testCreatePostAndReturnsDto(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('post')
            ->once()
            ->with('groups', ['name' => 'New Group'])
            ->andReturn(['id' => 99, 'name' => 'New Group']);

        $repo = new GroupRepository($handler, 'groups', GroupDTO::class);
        $group = $repo->create(new GroupDTO(name: 'New Group'));

        self::assertSame(99, $group->id);
        self::assertSame('New Group', $group->name);
    }

    public function testDeleteDelegatesToHandler(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->expects('delete')
            ->with('groups/7')
            ->once()
            ->andReturn([]);

        $repo = new GroupRepository($handler, 'groups', GroupDTO::class);
        $repo->delete(7);
    }
}
