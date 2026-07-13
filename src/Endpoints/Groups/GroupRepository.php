<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Groups;

use ZammadAPIClient\Core\AbstractRepository;

/**
 * Repository for the `/api/v1/groups` endpoint.
 *
 * Groups in Zammad are used to assign tickets to teams. A ticket belongs to
 * exactly one group, which determines routing, SLAs, and visibility. This
 * repository provides full CRUD access to group resources.
 *
 * @extends AbstractRepository<GroupDTO>
 */
final class GroupRepository extends AbstractRepository
{
    /**
     * Returns 'groups' — the JSON array key used in Zammad's paginated group list response.
     *
     * Zammad wraps paginated results in `{"groups": [...], "assets": {...}}`;
     * this key tells {@see \ZammadAPIClient\Core\AbstractRepository::extractItems()} where to find the items.
     */
    protected function getListKey(): string
    {
        return 'groups';
    }
}
