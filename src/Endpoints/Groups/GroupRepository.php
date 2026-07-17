<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Groups;

use ZammadAPIClient\Core\AbstractRepository;
use ZammadAPIClient\Core\Contracts\DeletableInterface;

/**
 * Repository for the `/api/v1/groups` endpoint.
 *
 * Groups in Zammad are used to assign tickets to teams. A ticket belongs to
 * exactly one group, which determines routing, SLAs, and visibility. This
 * repository provides full CRUD access to group resources.
 *
 * @extends AbstractRepository<GroupDTO>
 */
final class GroupRepository extends AbstractRepository implements DeletableInterface
{
    protected function getListKey(): string
    {
        return 'groups';
    }

    public function delete(int $id): void
    {
        $this->handler->delete("{$this->resourcePath}/{$id}");
    }
}
