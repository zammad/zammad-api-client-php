<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core\Transport;

use Psr\Http\Message\ResponseInterface;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;

final class ImpersonationHandler implements RequestHandlerInterface
{
    public function __construct(
        private RequestHandlerInterface $inner,
        private int|string $userId,
    ) {
    }

    public function request(
        string $method,
        string $uri,
        array $options = [],
    ): array {
        if (!isset($options['headers']) || !is_array($options['headers'])) {
            $options['headers'] = [];
        }

        $options['headers']['From'] = (string) $this->userId;

        return $this->inner->request($method, $uri, $options);
    }

    public function get(string $uri, array $query = []): array
    {
        if (!empty($query)) {
            $uri .= '?' . http_build_query($query);
        }

        return $this->request('GET', $uri);
    }

    public function post(string $uri, array $body = []): array
    {
        return $this->request('POST', $uri, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($body, JSON_THROW_ON_ERROR),
        ]);
    }

    public function put(string $uri, array $body = []): array
    {
        return $this->request('PUT', $uri, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($body, JSON_THROW_ON_ERROR),
        ]);
    }

    public function delete(string $uri): array
    {
        return $this->request('DELETE', $uri);
    }

    public function getRaw(string $uri, array $query = [], array $headers = []): string
    {
        $headers['From'] = (string) $this->userId;

        return $this->inner->getRaw($uri, $query, $headers);
    }

    public function getLastResponse(): ?ResponseInterface
    {
        return $this->inner->getLastResponse();
    }
}
