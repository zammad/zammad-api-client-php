<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Repository;

use InvalidArgumentException;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Endpoints\Groups\GroupDTO;
use ZammadAPIClient\Endpoints\Groups\GroupRepository;
use ZammadAPIClient\Endpoints\Links\LinkDTO;
use ZammadAPIClient\Endpoints\Links\LinkRepository;
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
use ZammadAPIClient\Endpoints\Organizations\OrganizationDTO;
use ZammadAPIClient\Endpoints\Organizations\OrganizationRepository;
use ZammadAPIClient\Endpoints\Users\UserDTO;
use ZammadAPIClient\Endpoints\Users\UserRepository;

/**
 * Single source of truth for repository wiring (API path + DTO class).
 *
 * Adding a resource: one entry in DEFINITIONS.
 *
 * @internal This class is not intended for direct use by consumers.
 *           Access repositories via {@see \ZammadAPIClient\ZammadClient::repo()}.
 */
final class RepositoryRegistry
{
    /** @var array<class-string, array{path: string, dto: class-string<DTOInterface>}> */
    public const DEFINITIONS = [
        TicketRepository::class => ['path' => 'tickets', 'dto' => TicketDTO::class],
        UserRepository::class => ['path' => 'users', 'dto' => UserDTO::class],
        OrganizationRepository::class => ['path' => 'organizations', 'dto' => OrganizationDTO::class],
        GroupRepository::class => ['path' => 'groups', 'dto' => GroupDTO::class],
        LinkRepository::class => ['path' => 'links', 'dto' => LinkDTO::class],
        TicketArticleRepository::class => ['path' => 'ticket_articles', 'dto' => TicketArticleDTO::class],
        TicketStateRepository::class => ['path' => 'ticket_states', 'dto' => TicketStateDTO::class],
        TicketPriorityRepository::class => ['path' => 'ticket_priorities', 'dto' => TicketPriorityDTO::class],
        TagRepository::class => ['path' => 'tags', 'dto' => TagDTO::class],
        TextModuleRepository::class => ['path' => 'text_modules', 'dto' => TextModuleDTO::class],
    ];

    /**
     * Returns the API path and DTO class wired to the given repository.
     *
     * Used by {@see \ZammadAPIClient\ZammadClient::repo()} to instantiate a
     * repository with the correct $resourcePath and $dtoClass arguments.
     *
     * @param class-string $repositoryClass Repository class whose wiring is requested.
     * @return array{path: string, dto: class-string<DTOInterface>}
     * @throws \InvalidArgumentException If $repositoryClass is not registered in DEFINITIONS.
     */
    public static function definition(string $repositoryClass): array
    {
        if (!array_key_exists($repositoryClass, self::DEFINITIONS)) {
            throw new InvalidArgumentException("Unknown repository: {$repositoryClass}");
        }

        return self::DEFINITIONS[$repositoryClass];
    }
}
