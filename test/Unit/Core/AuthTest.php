<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Core\Auth;

#[Group('unit')]
final class AuthTest extends TestCase
{
    public function testTokenBuildsZammadTokenHeader(): void
    {
        self::assertSame('Token token=secret', Auth::token('secret')->header());
    }

    public function testOAuth2BuildsBearerHeader(): void
    {
        self::assertSame('Bearer secret', Auth::oauth2('secret')->header());
    }

    public function testBasicBuildsBase64EncodedHeader(): void
    {
        self::assertSame(
            'Basic ' . base64_encode('admin@example.com:hunter2'),
            Auth::basic('admin@example.com', 'hunter2')->header(),
        );
    }

    public function testEmptyTokenIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API token must not be empty.');

        Auth::token('');
    }

    public function testWhitespaceOnlyTokenIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Auth::token("  \t ");
    }

    public function testEmptyOAuth2TokenIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OAuth2 token must not be empty.');

        Auth::oauth2('');
    }

    public function testEmptyUsernameIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Username must not be empty.');

        Auth::basic('', 'hunter2');
    }

    /**
     * RFC 7617 permits an empty password, so only the username is mandatory.
     */
    public function testEmptyPasswordIsAccepted(): void
    {
        self::assertSame(
            'Basic ' . base64_encode('admin:'),
            Auth::basic('admin', '')->header(),
        );
    }

    public function testDebugOutputDoesNotLeakTheCredential(): void
    {
        $dump = print_r(Auth::token('super-secret'), true);

        self::assertStringNotContainsString('super-secret', $dump);
        self::assertStringContainsString('redacted', $dump);
    }
}
