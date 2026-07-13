<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Contracts;

use ZammadAPIClient\Core\AbstractRepository;

interface ClientInterface
{
    /**
     * Returns a memoized repository for the given endpoint.
     *
     * @template T of AbstractRepository
     * @param class-string<T> $repositoryClass
     * @return T
     */
    public function repo(string $repositoryClass): AbstractRepository;
}
