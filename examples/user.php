<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/config.php';

use ZammadAPIClient\Client;
use ZammadAPIClient\ResourceType;

$client = new Client($zammad_api_client_config);

//
// Create a user
//
$email_address = 'api_test@example.com';

$user_data = [
    'login' => $email_address,
    'email' => $email_address,
];

$user = $client->resource( ResourceType::USER );
$user->setValues($user_data);
$user->save();
exitOnError($user);

$user_id = $user->getID(); // same as getValue('id')

//
// Fetch user
//
$user = $client->resource( ResourceType::USER )->get($user_id);
exitOnError($user);
print_r( $user->getValues() );

//
// Search user
//
$users = $client->resource( ResourceType::USER )->search($email_address);
if ( !is_array($users) ) {
    exitOnError($users);
}
else {
    print 'Found ' . count($users) . ' user(s) with email address ' . $email_address . "\n";
}

//
// Delete created user
//
$user->delete();
exitOnError($user);

function exitOnError($object)
{
    if ( $object->hasError() ) {
        print $object->getError() . "\n";
        exit(1);
    }
}
