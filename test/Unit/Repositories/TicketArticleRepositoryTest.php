<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Repositories;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleDTO;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleRepository;

#[Group('unit')]
final class TicketArticleRepositoryTest extends MockeryTestCase
{
    public function testGetForTicketReturnsTicketArticleDtos(): void
    {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with('ticket_articles/by_ticket/5', ['page' => '1', 'per_page' => '100', 'expand' => 'true'])
            ->andReturn([
                'ticket_articles' => [
                    ['id' => 1, 'ticket_id' => 5, 'type' => 'note', 'body' => 'hi'],
                ],
            ]);

        $repo = new TicketArticleRepository($handler, 'ticket_articles', TicketArticleDTO::class);
        $articles = iterator_to_array($repo->getForTicket(5));

        self::assertCount(1, $articles);
        self::assertInstanceOf(TicketArticleDTO::class, $articles[0]);
        self::assertSame(5, $articles[0]->ticket_id);
        self::assertSame('hi', $articles[0]->body);
    }
}
