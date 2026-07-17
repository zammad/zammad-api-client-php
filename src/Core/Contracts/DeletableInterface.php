<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Contracts;

interface DeletableInterface
{
    /**
     * Permanently deletes the resource with the given ID.
     *
     * The operation is irreversible. Zammad returns HTTP 200 with the deleted
     * resource's data; this method discards that response body.
     *
     * Only repositories for endpoints that support deletion implement this
     * interface. Repositories for immutable resources (e.g. Articles)
     * do NOT have a delete() method — attempting to call it results in a
     * PHP fatal error instead of a misleading 404 API response.
     *
     * @throws \ZammadAPIClient\Exceptions\AuthenticationException If the caller lacks permission.
     */
    public function delete(int $id): void;
}
