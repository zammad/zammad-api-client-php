<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Repositories;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\Tags\TagDTO;
use ZammadAPIClient\Endpoints\Tags\TagRepository;

#[Group('unit')]
final class TagRepositoryTest extends MockeryTestCase
{
    public function testAllPaginatesStringTags(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('tags', ['page' => '1', 'per_page' => '2', 'object' => 'Ticket', 'o_id' => '1'])
            ->andReturn(['tags' => ['urgent', 'bug']]);
        $handler->shouldReceive('get')
            ->once()
            ->with('tags', ['page' => '2', 'per_page' => '2', 'object' => 'Ticket', 'o_id' => '1'])
            ->andReturn([]);

        $repo = new TagRepository($handler, 'tags', TagDTO::class, 2);
        $tags = iterator_to_array($repo->all(['object' => 'Ticket', 'o_id' => '1']));

        self::assertCount(2, $tags);
        self::assertContainsOnlyInstancesOf(TagDTO::class, $tags);
        self::assertSame('urgent', $tags[0]->value);
    }

    public function testAddReturnsResponse(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('post')
            ->once()
            ->with('tags/add', ['object' => 'Ticket', 'o_id' => 42, 'item' => 'urgent'])
            ->andReturn(['id' => 1, 'object' => 'Ticket', 'o_id' => 42, 'value' => 'urgent']);

        $repo = new TagRepository($handler, 'tags', TagDTO::class);
        $result = $repo->add('Ticket', 42, 'urgent');

        self::assertSame('urgent', $result['value']);
    }

    public function testRemoveReturnsResponse(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('delete')
            ->once()
            ->with('tags/remove?object=Ticket&o_id=42&item=urgent')
            ->andReturn([]);

        $repo = new TagRepository($handler, 'tags', TagDTO::class);
        $result = $repo->remove('Ticket', 42, 'urgent');

        self::assertSame([], $result);
    }

    public function testTagSearchReturnsResults(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('tag_search', ['term' => 'urg'])
            ->andReturn([['id' => 1, 'value' => 'urgent']]);

        $repo = new TagRepository($handler, 'tags', TagDTO::class);
        $result = $repo->tagSearch('urg');

        self::assertCount(1, $result);
        self::assertSame('urgent', $result[0]['value']);
    }
}
