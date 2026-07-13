<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TextModules;

use ZammadAPIClient\Core\AbstractRepository;

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
final class TextModuleRepository extends AbstractRepository
{
    /**
     * Returns 'text_modules' — the JSON array key in Zammad's paginated text-module list response.
     */
    protected function getListKey(): string
    {
        return 'text_modules';
    }

    /**
     * Bulk-imports text modules from a CSV string.
     *
     * Useful for seeding a new Zammad instance with an existing canned-response
     * library or synchronising templates managed in an external CMS. The CSV
     * format must match Zammad's expected schema (keyword, name, content, etc.).
     *
     * @param string $csv Raw CSV content, including the header row.
     */
    public function import(string $csv): void
    {
        $this->handler->post("{$this->resourcePath}/import", ['data' => $csv]);
    }
}
