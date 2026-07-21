<?php

/**
 * 02-crud — Create, read, and delete tickets.
 *
 * Run: php examples/cookbook/02-crud.php
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleType;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
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

$repo = $client->ticket();

// ── Create ──────────────────────────────────────────────
echo "── Create ──\n";
$created = $repo->create(new TicketDTO(
    title: 'Cookbook CRUD ' . date('Y-m-d H:i:s'),
    customer_id: 1,
    group_id: 1,
    article: [
        'subject' => 'CRUD test',
        'body'    => 'Created via cookbook 02-crud.',
        'type'    => TicketArticleType::Note->value,
    ],
));
echo "Created ticket #{$created->id}\n";
echo "  title: {$created->title}\n";

// ── Read ────────────────────────────────────────────────
echo "\n── Read ──\n";
$ticket = $repo->find($created->id);
echo "find({$created->id}): {$ticket->title}\n";

// ── Error ──────────────────────────────────────────────
echo "\n── Error handling ──\n";
try {
    $repo->find(999999);
} catch (NotFoundException $e) {
    echo "find(999999): NotFoundException\n";
}

// ── Delete ──────────────────────────────────────────────
echo "\n── Delete ──\n";
$repo->delete($created->id);
echo "Deleted ticket #{$created->id}\n";

echo "\nDone.\n";
