<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\TextModules;

use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Traits\HasTimestamps;
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
 * Server-assigned fields (`id`) default to null. Timestamp fields
 * (`created_at`, `updated_at`) are provided by
 * {@see \ZammadAPIClient\Core\Traits\HasTimestamps}.
 */
final class TextModuleDTO implements DTOInterface
{
    use HasTimestamps;
    use HydratesFromArray;
    use SerializesToArray;

    public function __construct(
        public readonly string $name,
        public readonly ?string $keywords = null,
        public readonly ?string $content = null,
        public readonly ?string $note = null,
        public readonly ?bool $active = null,
        public readonly ?int $id = null,
    ) {
    }
}
