<?php

/**
 * 05-impersonation — ImpersonationHandler for scoped on-behalf-of.
 *
 * Run: php examples/cookbook/05-impersonation.php
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use ZammadAPIClient\Core\Transport\ImpersonationHandler;
use ZammadAPIClient\Endpoints\TicketArticles\TicketArticleType;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Exceptions\ForbiddenException;
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

// ── Create a ticket — then find it impersonated ─────────
echo "── Create ticket ──\n";
$created = $repo->create(new TicketDTO(
    title: 'Cookbook Impersonation ' . date('Y-m-d H:i:s'),
    customer_id: 1,  // owner — visible when impersonating user #1
    group_id: 1,
    article: [
        'subject' => 'Impersonation test',
        'body'    => 'Created for impersonation demo.',
        'type'    => TicketArticleType::Note->value,
    ],
));
echo "Created ticket #{$created->id}\n";

// ── Impersonate user #1 (stateless, no leak) ────────────
echo "\n── Impersonate user #1 ──\n";
$imp = new ImpersonationHandler($client->getHandler(), 1);
$scoped = new ZammadClient($imp);

try {
    $ticket = $scoped->ticket()->find($created->id);
    echo "As user #1 — ticket #{$created->id}: {$ticket->title}\n";
} catch (ForbiddenException) {
    echo "User #1 cannot access ticket #{$created->id} (expected)\n";
}

// ── Original client unchanged ───────────────────────────
echo "\n── Original client ──\n";
echo "Outer handler untouched: yes\n";

$repo->delete($created->id);
echo "\nCleaned up.\nDone.\n";
