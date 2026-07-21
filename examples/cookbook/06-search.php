<?php

/**
 * 06-search — Full-text search via search() and searchList().
 *
 * Run: php examples/cookbook/06-search.php
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleType;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
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

// ── Create a searchable ticket ──────────────────────────
$ticket = $repo->create(new TicketDTO(
    title: 'Cookbook Searchable ' . date('Y-m-d H:i:s'),
    customer_id: 1,
    group_id: 1,
    article: [
        'subject' => 'Searchable ticket',
        'body'    => 'This ticket is used for search demos.',
        'type'    => TicketArticleType::Note->value,
    ],
));
echo "Created ticket #{$ticket->id}\n\n";

// ── search() streaming ──────────────────────────────────
echo "── search('cookbook') streaming ──\n";
$count = 0;
foreach ($repo->search('cookbook') as $result) {
    $count++;
}
echo "Results: {$count}\n";

// ── searchList() with total count ───────────────────────
echo "\n── searchList pagination ──\n";
$all = $repo->searchList('*');
echo "searchList('*'):   {$all->getTotalCount()} total\n";

$list = $repo->searchList('cookbook');
$list->page(1);
echo "searchList('cookbook') page 1: " . count($list) . " results\n";

$repo->delete($ticket->id);
echo "\nCleaned up.\nDone.\n";
