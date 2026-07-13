<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Traits;

use ZammadAPIClient\Core\DtoHydrator;

/**
 * Provides a reflection-based `fromArray()` implementation for DTOs.
 *
 * Delegates to {@see \ZammadAPIClient\Core\DtoHydrator::hydrate()}, which
 * inspects the consuming class's constructor to determine parameter names and
 * types, then maps matching keys from the raw API array using
 * {@see \ZammadAPIClient\Core\Cast}. The constructor is the sole schema
 * definition — no separate mapping configuration is needed.
 *
 * When the API field name differs from the constructor parameter, or when
 * a fallback key must be tried (e.g. `owner_id` vs `owner`), override
 * `fromArray()` in the specific DTO and call `parent::fromArray()` only
 * after normalising the array. See {@see \ZammadAPIClient\Endpoints\Tickets\TicketDTO}
 * for an example.
 */
trait HydratesFromArray
{
    /**
     * Constructs a DTO from a raw API response array.
     *
     * Parameter names drive the field lookup: a constructor parameter named
     * `$ticketId` will read $data['ticketId']. The hydration is case-sensitive
     * and does not snake-case-convert parameter names.
     *
     * @param array<string, mixed> $data Raw JSON-decoded API response.
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return DtoHydrator::hydrate(static::class, $data);
    }
}
