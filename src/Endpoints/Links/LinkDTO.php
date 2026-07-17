<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Links;

use DateTimeImmutable;
use ZammadAPIClient\Core\Contracts\DTOInterface;
use ZammadAPIClient\Core\Traits\HydratesFromArray;
use ZammadAPIClient\Core\Traits\SerializesToArray;

/**
 * Represents a ticket link in Zammad (`/api/v1/links`).
 *
 * Links connect two tickets together. Zammad supports three link types:
 *  - `normal`   — basic association between two tickets.
 *  - `parent`   — hierarchical parent relationship.
 *  - `child`    — hierarchical child relationship.
 *
 * Server-assigned fields default to null so the DTO can represent
 * the result of both creation and listing operations.
 */
final class LinkDTO implements DTOInterface
{
    use HydratesFromArray;
    use SerializesToArray;

    public function __construct(
        public readonly ?int $id = null,
        public readonly ?int $link_type_id = null,
        public readonly ?string $link_type = null,
        public readonly ?string $link_object_source = null,
        public readonly ?int $link_object_source_value = null,
        public readonly ?string $link_object_target = null,
        public readonly ?int $link_object_target_value = null,
        public readonly ?DateTimeImmutable $created_at = null,
        public readonly ?DateTimeImmutable $updated_at = null,
    ) {
    }
}
