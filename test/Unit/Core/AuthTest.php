<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * A token read from a file or an environment variable commonly carries a
     * trailing newline. Untrimmed it reaches PSR-7, which rejects it as an
     * invalid header value and blames the transport; leading whitespace is
     * worse, surviving into the header and coming back as a plain 401.
     *
     * @return iterable<string, array{string}>
     */
    public static function paddedTokens(): iterable
    {
        yield 'trailing newline' => ["secret\n"];
        yield 'trailing space'   => ['secret '];
        yield 'leading space'    => [' secret'];
        yield 'leading tab'      => ["\tsecret"];
        yield 'both sides'       => ["  secret\r\n"];
    }

    #[DataProvider('paddedTokens')]
    public function testSurroundingWhitespaceIsStripped(string $padded): void
    {
        self::assertSame('Token token=secret', Auth::token($padded)->header());
    }

    public function testUsernameIsTrimmedButPasswordIsNot(): void
    {
        self::assertSame(
            'Basic ' . base64_encode('admin: hunter2 '),
            Auth::basic(' admin ', ' hunter2 ')->header(),
        );
    }

    public function testDebugOutputDoesNotLeakTheCredential(): void
    {
        $dump = print_r(Auth::token('super-secret'), true);

        self::assertStringNotContainsString('super-secret', $dump);
        self::assertStringContainsString('redacted', $dump);
    }

    /**
     * An empty username throws from inside {@see Auth::basic()}, so that frame —
     * password and all — lands in the stack trace. `#[\SensitiveParameter]` is what
     * keeps the password out of it; without the attribute this test fails.
     */
    public function testStackTraceDoesNotLeakTheBasicAuthPassword(): void
    {
        if (PHP_VERSION_ID < 80200) {
            self::markTestSkipped('#[\SensitiveParameter] is honoured from PHP 8.2 on.');
        }
        if (ini_get('zend.exception_ignore_args')) {
            self::markTestSkipped('Arguments are stripped from traces by zend.exception_ignore_args.');
        }

        try {
            Auth::basic('', 'hunter2');
            self::fail('Expected an InvalidArgumentException for the empty username.');
        } catch (InvalidArgumentException $e) {
            self::assertStringNotContainsString('hunter2', $e->getTraceAsString());
            self::assertStringContainsString('SensitiveParameterValue', $e->getTraceAsString());
        }
    }
}
