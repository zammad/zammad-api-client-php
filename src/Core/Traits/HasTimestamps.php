<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Traits;

use DateTimeImmutable;

/**
 * Provides createdAt() / updatedAt() helpers for DTOs with timestamp properties.
 *
 * Consuming DTOs must declare:
 *  - `public ?DateTimeImmutable $created_at = null`
 *  - `public ?DateTimeImmutable $updated_at = null`
 *
 * The methods return the respective DateTimeImmutable or null, giving callers
 * a typed accessor alternative to accessing the raw property. IDE autocomplete
 * and static analysis benefit from the explicit return type.
 */
trait HasTimestamps
{
    public function createdAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updated_at;
    }
}
