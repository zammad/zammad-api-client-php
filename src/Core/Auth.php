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
     * `$password` is marked `#[\SensitiveParameter]` so that it is redacted from
     * stack traces: an empty username throws from this very frame, and PHP's
     * default `zend.exception_ignore_args=0` would otherwise print the password
     * as a literal argument. The attribute is inert on PHP 8.1.
     *
     * @throws InvalidArgumentException If $user is empty.
     */
    public static function basic(string $user, #[\SensitiveParameter] string $password): self
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

    /**
     * Returns the value with surrounding whitespace removed.
     *
     * Trimming is not cosmetic. A credential read from a file or an environment
     * variable commonly carries a trailing newline, which PSR-7 rejects as an
     * invalid header value — the error then points at the transport rather than
     * at the token. Leading whitespace is worse: it survives into the header and
     * the request simply comes back 401.
     */
    private static function requireNonEmpty(string $value, string $label): string
    {
        $trimmed = trim($value);

        return $trimmed === ''
            ? throw new InvalidArgumentException("{$label} must not be empty.")
            : $trimmed;
    }
}
