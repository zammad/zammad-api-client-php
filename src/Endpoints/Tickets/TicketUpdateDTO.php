<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Tickets;

use ZammadAPIClient\Core\Contracts\PatchableInterface;

/**
 * Represents a partial ticket update payload for {@see \ZammadAPIClient\Core\AbstractRepository::patch()}.
 *
 * Only the fields that should be changed need to be set; null fields are omitted
 * from the API request, leaving those fields unchanged on the server. This avoids
 * the need to first fetch the full ticket just to modify one attribute.
 *
 * Example — change only the state and owner:
 * ```php
 * $client->repo(TicketRepository::class)->patch(42, new TicketUpdateDTO(
 *     state_id: 3,
 *     owner_id: 7,
 * ));
 * ```
 *
 * This DTO is intentionally separate from {@see TicketDTO} to make the set of
 * mutable fields explicit. TicketDTO includes read-only server fields (`number`,
 * `created_at`, etc.) that must never be sent back in an update request.
 */
final class TicketUpdateDTO implements PatchableInterface
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?int $state_id = null,
        public readonly ?int $priority_id = null,
        public readonly ?int $group_id = null,
        public readonly ?int $owner_id = null,
        public readonly ?int $customer_id = null,
        public readonly ?string $note = null,
        public readonly ?string $pending_time = null,
    ) {
    }

    /**
     * Returns only the non-null fields as an array for the API request body.
     *
     * Called automatically by {@see \ZammadAPIClient\Core\AbstractRepository::patch()}
     * when it detects a {@see \ZammadAPIClient\Core\Contracts\PatchableInterface}.
     *
     * @return array<string, mixed>
     */
    public function toPatchArray(): array
    {
        $vars = get_object_vars($this);
        return array_filter($vars, fn($v) => $v !== null);
    }
}
