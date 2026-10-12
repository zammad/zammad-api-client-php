<?php

/**
 * 07-custom-http-client — bring your own PSR-18 client instead of Guzzle.
 *
 * Guzzle is only the default. Any PSR-18 client works: build a RequestHandler
 * yourself and hand it to the ZammadClient constructor. Credentials, retry
 * behaviour and logging are configured exactly as with ZammadClient::create().
 *
 * This recipe uses Symfony HttpClient; the same shape applies to Buzz, Artax,
 * or anything else implementing Psr\Http\Client\ClientInterface.
 *
 * For the ordinary case, see 00-connect.php.
 *
 * See docs/custom-http-client.md for Laravel/Symfony container wiring and for
 * the security notes on timeouts, TLS verification and redirects.
 *
 * Requirements:
 *   composer require symfony/http-client nyholm/psr7
 *
 * Run: php examples/cookbook/07-custom-http-client.php
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

use ZammadAPIClient\Core\Auth;
use ZammadAPIClient\Core\Transport\RequestHandler;
use ZammadAPIClient\ZammadClient;

$url   = getenv('ZAMMAD_URL')   ?: 'http://localhost:3000';
$token = getenv('ZAMMAD_TOKEN') ?: '';

if ($token === '') {
    fwrite(STDERR, "Set ZAMMAD_TOKEN\n");
    exit(1);
}

// No Authorization header here: PSR-18 has no concept of default headers, and
// Symfony's Psr18Client does not offer one. The credential is passed to the
// RequestHandler below, which applies it to every request it composes.
$httpClient = new Psr18Client(
    HttpClient::create([
        'headers' => ['User-Agent' => 'Zammad API PHP'],
        'timeout' => 30,
    ]),
);

$handler = new RequestHandler(
    $httpClient,
    new Psr17Factory(),
    $url,
    maxRetries: 3,
    auth: Auth::token($token),
);

$client = new ZammadClient($handler);

echo "Connected to {$url} (Symfony HttpClient)\n";
echo "Ticket #1: " . $client->ticket()->find(1)->title . "\n";
