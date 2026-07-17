<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TextModules;

use ZammadAPIClient\Core\AbstractRepository;
use ZammadAPIClient\Core\Contracts\DeletableInterface;

/**
 * Repository for the `/api/v1/text_modules` endpoint.
 *
 * Text modules are reusable canned-response templates that agents can insert
 * into ticket replies. They support Zammad's variable substitution syntax
 * (e.g. `#{ticket.title}`). This repository provides full CRUD access plus
 * a CSV bulk-import endpoint.
 *
 * @extends AbstractRepository<TextModuleDTO>
 */
final class TextModuleRepository extends AbstractRepository implements DeletableInterface
{
    protected function getListKey(): string
    {
        return 'text_modules';
    }

    public function delete(int $id): void
    {
        $this->handler->delete("{$this->resourcePath}/{$id}");
    }

    /**
     * @param string $csv Raw CSV content, including the header row.
     * @return array<string, mixed> The decoded API response body.
     */
    public function import(string $csv): array
    {
        return $this->handler->post("{$this->resourcePath}/import", ['data' => $csv]);
    }
}
