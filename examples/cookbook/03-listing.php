<?php

/**
 * 03-listing — all() streaming, list() pagination, totalCount().
 *
 * Run: php examples/cookbook/03-listing.php
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

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

$repo = $client->ticket();

// ── totalCount() — single API call ──────────────────────
echo "── Total count ──\n";
echo "totalCount(): {$repo->totalCount()} tickets\n";

// ── all() — streaming generator ─────────────────────────
echo "\n── all() streaming ──\n";
$count = 0;
foreach ($repo->all() as $ticket) {
    $count++;
    if ($count <= 3) {
        echo "  #{$ticket->id} {$ticket->title}\n";
    }
}
echo "Streamed {$count} tickets\n";

// ── list() — page navigation ────────────────────────────
echo "\n── list() pagination ──\n";
$list = $repo->list();
$list->page(1);
echo "Page 1: " . count($list) . " tickets\n";

if ($list[0] !== null) {
    echo "  First: {$list[0]->title}\n";
}

echo "\nDone.\n";
