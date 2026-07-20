<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Users;

use ZammadAPIClient\Core\Repository\AbstractRepository;
use ZammadAPIClient\Core\Contracts\DeletableInterface;

/**
 * Repository for the `/api/v1/users` endpoint.
 *
 * Users in Zammad represent both agents (staff who work on tickets) and
 * customers (end-users who submit tickets). The distinction is made via the
 * `role_ids` field. This repository provides full CRUD access plus a
 * CSV bulk-import endpoint for migrating users from another system.
 *
 * @extends AbstractRepository<UserDTO>
 */
final class UserRepository extends AbstractRepository implements DeletableInterface
{
    /**
     * Bulk-imports users from a CSV string.
     *
     * The CSV format must conform to Zammad's import specification. Zammad
     * validates each row and skips invalid records rather than aborting the
     * entire import. The response body contains an import summary.
     *
     * Useful for initial data migration or scheduled synchronisation with an
     * external identity provider (when LDAP sync is not an option).
     *
     * @param string $csv Raw CSV content, including the header row.
     */
    /**
     * @return array<string, mixed>
     */
    public function import(string $csv): array
    {
        return $this->handler->post("{$this->resourcePath}/import", ['data' => $csv]);
    }

    public function delete(int $id): void
    {
        $this->handler->delete("{$this->resourcePath}/{$id}");
    }
}
