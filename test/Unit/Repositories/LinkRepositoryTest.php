<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Repositories;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\Links\LinkDTO;
use ZammadAPIClient\Endpoints\Links\LinkRepository;

#[Group('unit')]
final class LinkRepositoryTest extends MockeryTestCase
{
    public function testAllPaginatesLinks(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('links', ['page' => '1', 'per_page' => '2', 'link_object' => 'Ticket', 'link_object_value' => '1'])
            ->andReturn(['links' => [
                ['id' => 1, 'link_type' => 'normal'],
                ['id' => 2, 'link_type' => 'parent'],
            ]]);
        $handler->shouldReceive('get')
            ->once()
            ->with('links', ['page' => '2', 'per_page' => '2', 'link_object' => 'Ticket', 'link_object_value' => '1'])
            ->andReturn([]);

        $repo = new LinkRepository($handler, 'links', LinkDTO::class, 2);

        $links = iterator_to_array($repo->all(['object' => 'Ticket', 'object_id' => 1]));

        self::assertCount(2, $links);
        self::assertContainsOnlyInstancesOf(LinkDTO::class, $links);
    }

    public function testAddCreatesLink(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('post')
            ->once()
            ->with('links/add', [
                'link_type' => 'normal',
                'link_object_source' => 'Ticket',
                'link_object_source_number' => '84001',
                'link_object_target' => 'Ticket',
                'link_object_target_value' => 2,
            ])
            ->andReturn(['id' => 99]);

        $repo = new LinkRepository($handler, 'links', LinkDTO::class);
        $result = $repo->add('normal', 'Ticket', '84001', 'Ticket', 2);

        self::assertSame(99, $result['id']);
    }

    public function testRemoveDeletesLink(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('delete')
            ->once()
            ->with(
                'links/remove?link_type=normal&link_object_source=Ticket&link_object_source_value=84001'
                . '&link_object_target=Ticket&link_object_target_value=2',
            )
            ->andReturn([]);

        $repo = new LinkRepository($handler, 'links', LinkDTO::class);
        $result = $repo->remove('normal', 'Ticket', 84001, 'Ticket', 2);

        self::assertSame([], $result);
    }

    public function testGetListKey(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $repo = new LinkRepository($handler, 'links', LinkDTO::class);

        $ref = new \ReflectionClass($repo);
        $method = $ref->getMethod('getListKey');

        self::assertSame('links', $method->invoke($repo));
    }
}
