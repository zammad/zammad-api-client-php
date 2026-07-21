<?php

/**
 * Zammad API Client — Non-Guzzle setup (Symfony HttpClient + Nyholm PSR-17).
 *
 * Requirements:
 *   composer require symfony/http-client nyholm/psr7
 *
 * Run: php examples/cookbook/00-slim.php
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\HttpClient\Psr18Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use ZammadAPIClient\Core\Transport\RequestHandler;
use ZammadAPIClient\ZammadClient;

$url   = getenv('ZAMMAD_URL')   ?: 'http://localhost:3000';
$token = getenv('ZAMMAD_TOKEN') ?: '';

if ($token === '') {
    fwrite(STDERR, "Set ZAMMAD_TOKEN\n");
    exit(1);
}

$symfonyClient = new Psr18Client(
    (new \Symfony\Component\HttpClient\HttpClient())->create([
        'headers' => [
            'User-Agent'    => 'Zammad API PHP',
            'Authorization' => "Token token={$token}",
        ],
    ]),
);

$psr17 = new Psr17Factory();

$handler = new RequestHandler($symfonyClient, $psr17, $url);
$client = new ZammadClient($handler);

echo "Connected to {$url} (Symfony HttpClient)\n";
echo "Ticket #1: " . $client->ticket()->find(1)->title . "\n";
