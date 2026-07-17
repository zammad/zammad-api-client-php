<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration\Traits;

use ZammadAPIClient\ZammadClient;

/**
 * @mixin \PHPUnit\Framework\TestCase
 */
trait CreatesZammadClient
{
    protected static function createZammadClient(): ZammadClient
    {
        $url   = getenv('ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL') ?: null;
        $token = getenv('ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TOKEN') ?: null;
        $user  = getenv('ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_USERNAME') ?: null;
        $pass  = getenv('ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_PASSWORD') ?: null;

        if ($url === null || $url === '') {
            self::markTestSkipped('Integration tests require ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL.');
        }

        if (!str_contains($url, '/api/')) {
            $url = rtrim($url, '/') . '/api/v1';
        }

        if ($token !== null && $token !== '') {
            return ZammadClient::withToken($url, $token);
        }

        if ($user !== null && $pass !== null) {
            return ZammadClient::withBasicAuth($url, $user, $pass);
        }

        self::markTestSkipped('Missing ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TOKEN or USERNAME/PASSWORD.');
    }
}
