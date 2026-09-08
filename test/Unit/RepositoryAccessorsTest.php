<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Endpoints\Groups\GroupDTO;
use ZammadAPIClient\Endpoints\Groups\GroupRepository;
use ZammadAPIClient\Endpoints\Links\LinkDTO;
use ZammadAPIClient\Endpoints\Links\LinkRepository;
use ZammadAPIClient\Endpoints\Organizations\OrganizationDTO;
use ZammadAPIClient\Endpoints\Organizations\OrganizationRepository;
use ZammadAPIClient\Endpoints\Tags\TagDTO;
use ZammadAPIClient\Endpoints\Tags\TagRepository;
use ZammadAPIClient\Endpoints\TextModules\TextModuleDTO;
use ZammadAPIClient\Endpoints\TextModules\TextModuleRepository;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleDTO;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleRepository;
use ZammadAPIClient\Endpoints\TicketPriorities\TicketPriorityDTO;
use ZammadAPIClient\Endpoints\TicketPriorities\TicketPriorityRepository;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
use ZammadAPIClient\Endpoints\TicketStates\TicketStateDTO;
use ZammadAPIClient\Endpoints\TicketStates\TicketStateRepository;
use ZammadAPIClient\Endpoints\Users\UserDTO;
use ZammadAPIClient\Endpoints\Users\UserRepository;
use ZammadAPIClient\ZammadClient;

/**
 * Verifies the typed repository accessors (`$client->ticket()`, `$client->user()`, …)
 * return correctly wired, memoized repositories — i.e. the public API documented
 * in the README actually goes through the accessors and produces working repositories.
 */
#[Group('unit')]
final class RepositoryAccessorsTest extends MockeryTestCase
{
    /**
     * @return array<string, array{string, class-string, string, class-string}>
     */
    public static function accessorProvider(): array
    {
        return [
            'ticket' => ['ticket', TicketRepository::class, 'tickets', TicketDTO::class],
            'user' => ['user', UserRepository::class, 'users', UserDTO::class],
            'organization' => ['organization', OrganizationRepository::class, 'organizations', OrganizationDTO::class],
            'group' => ['group', GroupRepository::class, 'groups', GroupDTO::class],
            'ticketArticle' => [
                'ticketArticle', TicketArticleRepository::class, 'ticket_articles', TicketArticleDTO::class,
            ],
            'ticketState' => ['ticketState', TicketStateRepository::class, 'ticket_states', TicketStateDTO::class],
            'ticketPriority' => [
                'ticketPriority', TicketPriorityRepository::class, 'ticket_priorities', TicketPriorityDTO::class,
            ],
            'tag' => ['tag', TagRepository::class, 'tags', TagDTO::class],
            'textModule' => ['textModule', TextModuleRepository::class, 'text_modules', TextModuleDTO::class],
            'link' => ['link', LinkRepository::class, 'links', LinkDTO::class],
        ];
    }

    /**
     * @param class-string $repoClass
     * @param class-string $dtoClass
     */
    #[DataProvider('accessorProvider')]
    public function testAccessorReturnsWiredRepository(
        string $accessor,
        string $repoClass,
        string $path,
        string $dtoClass,
    ): void {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $handler->shouldReceive('get')
            ->once()
            ->with("{$path}/1", ['expand' => 'true'])
            ->andReturn(['id' => 1]);

        $client = new ZammadClient($handler);

        $repo = $client->{$accessor}();

        self::assertInstanceOf($repoClass, $repo);

        $dto = $repo->find(1);

        self::assertInstanceOf($dtoClass, $dto);
    }

    /**
     * @param class-string $repoClass
     */
    #[DataProvider('accessorProvider')]
    public function testAccessorIsMemoizedAndMatchesRepo(
        string $accessor,
        string $repoClass,
    ): void {
        $handler = Mockery::mock(RequestHandlerInterface::class);
        $client = new ZammadClient($handler);

        $first = $client->{$accessor}();
        $second = $client->{$accessor}();

        self::assertSame($first, $second);
        self::assertSame($first, $client->repo($repoClass));
    }
}
