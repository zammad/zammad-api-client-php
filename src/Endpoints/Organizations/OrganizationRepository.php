<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Organizations;

use ZammadAPIClient\Core\AbstractRepository;

/**
 * Repository for the `/api/v1/organizations` endpoint.
 *
 * Organizations group users (customers) under a shared company entity. A user
 * can belong to one primary organization and optionally multiple secondary ones.
 * This repository provides full CRUD access plus a CSV bulk-import endpoint.
 *
 * @extends AbstractRepository<OrganizationDTO>
 */
final class OrganizationRepository extends AbstractRepository
{
    /**
     * Returns 'organizations' — the JSON array key in Zammad's paginated organization list response.
     */
    protected function getListKey(): string
    {
        return 'organizations';
    }

    /**
     * Bulk-imports organizations from a CSV string.
     *
     * The CSV format must conform to Zammad's import specification (see Zammad
     * documentation for required columns). Zammad processes the import
     * asynchronously; the response body is typically empty on success.
     *
     * This endpoint is useful for migrating organization data from another
     * helpdesk system or synchronising from an external directory.
     *
     * @param string $csv Raw CSV content, including the header row.
     */
    public function import(string $csv): void
    {
        $this->handler->post("{$this->resourcePath}/import", ['data' => $csv]);
    }
}
