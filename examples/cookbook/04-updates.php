<?php

/**
 * 04-updates — patch() partial update + TicketUpdateDTO.
 *
 * Run: php examples/cookbook/04-updates.php
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleType;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Endpoints\Tickets\TicketUpdateDTO;
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

// ── Create a ticket to work with ────────────────────────
$ticket = $repo->create(new TicketDTO(
    title: 'Cookbook Update ' . date('Y-m-d H:i:s'),
    customer_id: 1,
    group_id: 1,
    article: [
        'subject' => 'Before update',
        'body'    => 'This ticket will be updated.',
        'type'    => TicketArticleType::Note->value,
    ],
));
echo "Created ticket #{$ticket->id}\n";

// ── Patch via array ─────────────────────────────────────
echo "\n── patch() via array ──\n";
$updated = $repo->patch($ticket->id, ['title' => 'Patched array ' . date('H:i:s')]);
echo "title: {$updated->title}\n";

// ── Patch via TicketUpdateDTO ───────────────────────────
echo "\n── patch() via TicketUpdateDTO ──\n";
$dto = new TicketUpdateDTO(title: 'Patched DTO ' . date('H:i:s'));
$updated = $repo->patch($ticket->id, $dto);
echo "title: {$updated->title}\n";

// ── Resource wrapper (stateful) ─────────────────────────
echo "\n── Resource wrapper ──\n";
$resource = $repo->resource($ticket->id);
$resource->title = 'Resource ' . date('H:i:s');
$resource->save();
echo "title: {$resource->title}\n";

$repo->delete($ticket->id);
echo "\nCleaned up.\nDone.\n";
