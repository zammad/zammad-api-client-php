<?php

/**
 * Zammad API Client v3 — Cookbook
 *
 * Runnable recipes demonstrating the v3 API.
 * Run: php examples/cookbook.php
 *
 * Requirements: ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL and ..._TOKEN
 * (or ..._USERNAME / ..._PASSWORD for basic auth).
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleType;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Exceptions\NotFoundException;
use ZammadAPIClient\Factory\GuzzleClientFactory;
use ZammadAPIClient\ZammadClient;

$url = getenv('ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL') ?: 'http://localhost:3000';
$token = getenv('ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TOKEN') ?: '';
$user = getenv('ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_USERNAME') ?: 'admin@example.com';
$pass = getenv('ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_PASSWORD') ?: 'test';

$client = $token !== ''
    ? new ZammadClient(GuzzleClientFactory::withToken($url, $token))
    : new ZammadClient(GuzzleClientFactory::withBasicAuth($url, $user, $pass));

echo "✓ Client connected to {$url}\n";

// Typed shortcut — `$client->ticket()` returns TicketRepository
$repo = $client->ticket();

// ── Caching reference data ──────────────────────────────────────
// States and priorities rarely change. The simple static cache below
// avoids repeated HTTP calls within one script run. For production:
//   PSR-6/16 with Memcached: $cachePool->get('zammad.priorities')
//   Laravel: Cache::remember('zammad.priorities', 3600, fn() => ...)
//   Plain PHP (apcu): apcu_fetch('zammad.priorities') ?? refresh...
function memoize(string $key, callable $fn): mixed {
    static $cache = [];
    return $cache[$key] ??= $fn();
}

$priorities = memoize('priorities', fn() =>
    [...$client->ticketPriority()->all()]
);
$states = memoize('states', fn() =>
    [...$client->ticketState()->all()]
);

$normalPriorityId = null;
$openStateId = null;

foreach ($priorities as $p) {
    if (mb_stripos($p->name, 'normal') !== false) {
        $normalPriorityId = $p->id;
        break;
    }
}

foreach ($states as $s) {
    if (mb_stripos($s->name, 'open') !== false) {
        $openStateId = $s->id;
        break;
    }
}

// ── Recipe 1: Create a ticket to work with ──────────────────────
$hostTicket = $repo->create(new TicketDTO(
    title: 'Cookbook Host ' . date('Y-m-d H:i:s'),
    customer_id: 1,
    group_id: 1,
    priority_id: $normalPriorityId ?? 2,
    state_id: $openStateId ?? 1,
    article: [
        'subject' => 'Host ticket',
        'body'    => 'This is the ticket used by the cookbook recipes.',
        'type'    => TicketArticleType::Note->value,
    ],
));
$ticketId = $hostTicket->id;
echo "✓ Created host ticket #{$ticketId}: {$hostTicket->title}\n";

// ── Recipe 2: Fetch a ticket ────────────────────────────────────
$ticket = $repo->find($ticketId);
echo "✓ Fetched ticket #{$ticketId}: {$ticket->title}\n";

// ── Recipe 3: Stateful resource with changes tracking ───────────
$resource = $repo->resource($ticketId);
$resource->title = 'Updated via Resource ' . date('H:i:s');
$resource->save();
echo "✓ Ticket #{$resource->id} updated: {$resource->title}\n";

// ── Recipe 4: Create via DTO ────────────────────────────────────
$created = $repo->create(new TicketDTO(
    title: 'Cookbook Test ' . date('H:i:s'),
    customer_id: 1,
    group_id: 1,
    priority_id: $normalPriorityId ?? 2,
    state_id: $openStateId ?? 1,
    article: [
        'subject' => 'Cookbook Test',
        'body'    => 'Created via cookbook recipe 4',
        'type'    => TicketArticleType::Note->value,
    ],
));
echo "✓ Ticket #{$created->id} created: {$created->title}\n";

// ── Recipe 5: Paginated list with navigation ────────────────────
$list = $repo->list(['expand' => 'true']);
$list->page(1);
echo "✓ Page 1: " . count($list) . " tickets\n";

// ── Recipe 6: Error handling ────────────────────────────────────
try {
    $repo->find(999999);
} catch (NotFoundException $e) {
    echo "✓ NotFoundException caught: {$e->getMessage()}\n";
}

// ── Recipe 7: Delete a ticket via repository ────────────────────
$repo->delete($created->id);
echo "✓ Ticket #{$created->id} deleted\n";

// ── Recipe 8: On-Behalf-Of impersonation ────────────────────────
$imp = new \ZammadAPIClient\Core\Transport\ImpersonationHandler($client->getHandler(), 1);
$scoped = new ZammadClient($imp);
echo "   (acting as user #1) Ticket #{$ticketId} title: "
    . $scoped->ticket()->find($ticketId)->title . "\n";
echo "✓ Impersonation complete\n";

// ── Recipe 9: Search ────────────────────────────────────────────
$count = 0;
foreach ($repo->search('cookbook') as $t) {
    $count++;
}
echo "✓ Search found {$count} tickets\n";

// Clean up the host ticket
$repo->delete($ticketId);
echo "✓ Host ticket #{$ticketId} cleaned up\n";

echo "\nAll recipes executed.\n";
