<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Traits;

use ZammadAPIClient\Core\Repository\AbstractRepository;
use ZammadAPIClient\Endpoints\Groups\GroupRepository;
use ZammadAPIClient\Endpoints\Links\LinkRepository;
use ZammadAPIClient\Endpoints\Organizations\OrganizationRepository;
use ZammadAPIClient\Endpoints\Tags\TagRepository;
use ZammadAPIClient\Endpoints\TextModules\TextModuleRepository;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleRepository;
use ZammadAPIClient\Endpoints\TicketPriorities\TicketPriorityRepository;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
use ZammadAPIClient\Endpoints\TicketStates\TicketStateRepository;
use ZammadAPIClient\Endpoints\Users\UserRepository;

trait RepositoryAccessors
{
    abstract public function repo(string $repositoryClass): AbstractRepository;

    public function ticket(): TicketRepository
    {
        return $this->repo(TicketRepository::class);
    }

    public function user(): UserRepository
    {
        return $this->repo(UserRepository::class);
    }

    public function organization(): OrganizationRepository
    {
        return $this->repo(OrganizationRepository::class);
    }

    public function group(): GroupRepository
    {
        return $this->repo(GroupRepository::class);
    }

    public function ticketArticle(): TicketArticleRepository
    {
        return $this->repo(TicketArticleRepository::class);
    }

    public function ticketState(): TicketStateRepository
    {
        return $this->repo(TicketStateRepository::class);
    }

    public function ticketPriority(): TicketPriorityRepository
    {
        return $this->repo(TicketPriorityRepository::class);
    }

    public function tag(): TagRepository
    {
        return $this->repo(TagRepository::class);
    }

    public function textModule(): TextModuleRepository
    {
        return $this->repo(TextModuleRepository::class);
    }

    public function link(): LinkRepository
    {
        return $this->repo(LinkRepository::class);
    }
}
