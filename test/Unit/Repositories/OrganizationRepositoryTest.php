<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Repositories;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\Organizations\OrganizationDTO;
use ZammadAPIClient\Endpoints\Organizations\OrganizationRepository;

#[Group('unit')]
final class OrganizationRepositoryTest extends MockeryTestCase
{
    public function testAllPaginatesOrganizations(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('organizations', ['page' => '1', 'per_page' => '2'])
            ->andReturn(['organizations' => [
                ['id' => 1, 'name' => 'Org A'],
                ['id' => 2, 'name' => 'Org B'],
            ]]);
        $handler->shouldReceive('get')
            ->once()
            ->with('organizations', ['page' => '2', 'per_page' => '2'])
            ->andReturn([]);

        $repo = new OrganizationRepository($handler, 'organizations', OrganizationDTO::class, 2);
        $orgs = iterator_to_array($repo->all());

        self::assertCount(2, $orgs);
        self::assertContainsOnlyInstancesOf(OrganizationDTO::class, $orgs);
    }

    public function testImportPostsCsv(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('post')
            ->once()
            ->with('organizations/import', ['data' => "name\na"])
            ->andReturn([]);

        $repo = new OrganizationRepository($handler, 'organizations', OrganizationDTO::class);
        $repo->import("name\na");

        self::assertTrue(true);
    }

    public function testDeleteDelegatesToHandler(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->expects('delete')
            ->with('organizations/99')
            ->once()
            ->andReturn([]);

        $repo = new OrganizationRepository($handler, 'organizations', OrganizationDTO::class);
        $repo->delete(99);
    }
}
