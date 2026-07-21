<?php

/**
 * 01-quick-start — Client instantiation + find a ticket.
 *
 * Run: php examples/cookbook/01-quick-start.php
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use ZammadAPIClient\Exceptions\NotFoundException;
use ZammadAPIClient\Factory\GuzzleClientFactory;
use ZammadAPIClient\ZammadClient;

$url   = getenv('ZAMMAD_URL')   ?: 'http://localhost:3000';
$token = getenv('ZAMMAD_TOKEN') ?: '';
$user  = getenv('ZAMMAD_USERNAME') ?: '';
$pass  = getenv('ZAMMAD_PASSWORD') ?: '';

if ($token !== '') {
    $client = new ZammadClient(GuzzleClientFactory::withToken($url, $token));
} elseif ($user !== '' && $pass !== '') {
    $client = new ZammadClient(GuzzleClientFactory::withBasicAuth($url, $user, $pass));
} else {
    fwrite(STDERR, "Set ZAMMAD_TOKEN or ZAMMAD_USERNAME + ZAMMAD_PASSWORD\n");
    exit(1);
}

echo "Client connected to {$url}\n\n";

// Typed shortcuts — find a ticket
echo "── Ticket lookup ──\n";

$repo = $client->ticket();
try {
    $ticket = $repo->find(1);
    echo "find(1): #{$ticket->id} {$ticket->title}\n";
} catch (NotFoundException) {
    echo "find(1): no ticket #1 — run 02-crud.php first\n";
}

try {
    $user = $client->user()->find(1);
    echo "user(1):  {$user->firstname} {$user->lastname}\n";
} catch (NotFoundException) {
    echo "user(1):  no user #1\n";
}

echo "\nDone.\n";
