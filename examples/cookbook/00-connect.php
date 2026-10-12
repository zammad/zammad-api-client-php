<?php

/**
 * 00-connect — connect to Zammad.
 *
 * Standalone and copy-paste-ready. Recipes 01-06 repeat this block verbatim so
 * that each one runs on its own; nothing includes this file.
 *
 * To supply your own HTTP client instead, see 07-custom-http-client.php.
 *
 * Run: php examples/cookbook/00-connect.php
 */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use ZammadAPIClient\Core\Auth;
use ZammadAPIClient\ZammadClient;

$url   = getenv('ZAMMAD_URL')   ?: 'http://localhost:3000';
$token = getenv('ZAMMAD_TOKEN') ?: '';
$user  = getenv('ZAMMAD_USERNAME') ?: '';
$pass  = getenv('ZAMMAD_PASSWORD') ?: '';

if ($token !== '') {
    $client = ZammadClient::create($url, Auth::token($token));
} elseif ($user !== '' && $pass !== '') {
    $client = ZammadClient::create($url, Auth::basic($user, $pass));
} else {
    fwrite(STDERR, "Set ZAMMAD_TOKEN or ZAMMAD_USERNAME + ZAMMAD_PASSWORD\n");
    exit(1);
}

echo "Connected to {$url}\n";
