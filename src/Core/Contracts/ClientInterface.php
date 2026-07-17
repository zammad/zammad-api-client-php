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

    /**
     * Returns the underlying PSR-18 request handler for raw API access.
     *
     * Use this escape hatch when you need to call an endpoint that has no
     * dedicated repository (e.g. ticket deletion, direct `/tag_list` access).
     */
    public function getHandler(): RequestHandlerInterface;
}
