<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Contracts;

interface PatchableInterface
{
    /**
     * Returns only the fields that should be sent in a partial update request.
     *
     * @return array<string, mixed>
     */
    public function toPatchArray(): array;
}
