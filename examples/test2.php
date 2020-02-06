<?php

require __DIR__ . '/../vendor/autoload.php';

use ZammadAPIClient\Client;
use ZammadAPIClient\ResourceType;

$zammad_api_client_config = [
    'url' => 'https://marcel-presso.zammad.com',
    'http_token' => 'n96qaia_sQPshMn9wtruyoVPyX-kIK_W9a8syB1ckua_OK5TgEgL2aqZM8SJvsGe',
    // 'username' => 'xxx',
    // 'password' => 'xxx',
];

$client = new Client($zammad_api_client_config);

$users = $client->resource( ResourceType::USER )->search("Marcel");
if ( !is_array($users) ) {
    print $users->getError() . "\n";
    exit(1);
}

foreach ( $users as $user ) {
    print_r( $user->getValues() );
}
