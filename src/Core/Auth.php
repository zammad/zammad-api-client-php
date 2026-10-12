<?php

declare(strict_types=1);

namespace ZammadAPIClient\Core;

use InvalidArgumentException;

/**
 * Credentials for the Zammad API, as a transport-independent value.
 *
 *   $client = ZammadClient::create('https://zammad.example', Auth::token('your-token'));
 *
 * The constructor is private, so the only way to obtain an instance is through
 * one of the named constructors below. That makes the set of valid auth schemes
 * closed by construction rather than by convention.
 *
 * The resulting header is applied by {@see Transport\RequestHandler}
 * to every outgoing request, which keeps authentication independent of the
 * PSR-18 client in use — PSR-18 itself has no concept of default headers.
 */
final class Auth
{
    private function __construct(
        private readonly string $header,
    ) {
    }

    /**
     * Zammad personal access token — sends `Authorization: Token token=<token>`.
     *
     * @throws InvalidArgumentException If $token is empty.
     */
    public static function token(string $token): self
    {
        return new self('Token token=' . self::requireNonEmpty($token, 'API token'));
    }

    /**
     * OAuth2 access token — sends `Authorization: Bearer <token>`.
     *
     * @throws InvalidArgumentException If $token is empty.
     */
    public static function oauth2(string $token): self
    {
        return new self('Bearer ' . self::requireNonEmpty($token, 'OAuth2 token'));
    }

    /**
     * HTTP basic auth — sends `Authorization: Basic base64(user:password)`.
     *
     * An empty password is permitted (RFC 7617); the username is required.
     *
     * @throws InvalidArgumentException If $user is empty.
     */
    public static function basic(string $user, string $password): self
    {
        return new self(
            'Basic ' . base64_encode(self::requireNonEmpty($user, 'Username') . ':' . $password),
        );
    }

    /**
     * Returns the ready-to-send `Authorization` header value.
     *
     * @internal Consumed by the transport layer; not part of the public API.
     */
    public function header(): string
    {
        return $this->header;
    }

    /**
     * Keeps the credential out of var_dump(), print_r() and stack traces.
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['header' => '***redacted***'];
    }

    private static function requireNonEmpty(string $value, string $label): string
    {
        return trim($value) === ''
            ? throw new InvalidArgumentException("{$label} must not be empty.")
            : $value;
    }
}
