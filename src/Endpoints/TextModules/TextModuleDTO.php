<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TextModules;

use DateTimeImmutable;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Traits\HydratesFromArray;
use ZammadAPIClient\Core\Traits\SerializesToArray;

/**
 * Represents a Zammad text module resource (`/api/v1/text_modules`).
 *
 * Text modules are canned-response templates that agents can search and insert
 * into ticket replies. They support Zammad's variable interpolation syntax
 * (e.g. `#{ticket.title}`, `#{customer.firstname}`) so content is personalised
 * at insertion time.
 *
 * Key fields:
 *  - `name`     — Display name shown in the search result list.
 *  - `keywords` — Space-separated keywords that improve searchability without
 *                 appearing in the inserted text.
 *  - `content`  — The template body, optionally containing `#{...}` variables.
 *
 * Server-assigned fields (`id`, `created_at`, `updated_at`) default to null
 * and are populated by the API after `create()` or `find()`.
 */
final readonly class TextModuleDTO implements DTOInterface
{
    use HydratesFromArray;
    use SerializesToArray;

    public function __construct(
        public string $name,
        public ?string $keywords = null,
        public ?string $content = null,
        public ?string $note = null,
        public ?bool $active = null,
        public ?int $id = null,
        public ?DateTimeImmutable $created_at = null,
        public ?DateTimeImmutable $updated_at = null,
    ) {
    }
}
