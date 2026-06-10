<?php

/**
 * Example for Tag administration scope.
 *
 * Manage tags globally: list, create, rename and delete tags.
 * See https://docs.zammad.org/en/latest/api/ticket/tags.html#administration-scope
 */

use ZammadAPIClient\Client;
use ZammadAPIClient\ResourceType;

require __DIR__ . '/../vendor/autoload.php';

$client = new Client([
    'url'      => 'https://my.zammad.com',
    'username' => 'my-username',
    'password' => 'my-password',
]);

// List all tags (administration scope)
$tags = $client->resource(ResourceType::TAG)->all();
echo "All tags:\n";
foreach ($tags as $tag) {
    echo sprintf(
        "  ID: %s, Name: %s, Count: %s\n",
        $tag->getID(),
        $tag->getValue('name'),
        $tag->getValue('count')
    );
}

// Create a new tag
$tag = $client->resource(ResourceType::TAG);
$tag->setValue('name', 'example-tag');
$tag->save();

echo "\nTag 'example-tag' created.\n";

// Find the created tag to get its ID
$tag_id = null;
$tags = $client->resource(ResourceType::TAG)->all();
foreach ($tags as $t) {
    if ($t->getValue('name') === 'example-tag') {
        $tag_id = $t->getID();
        break;
    }
}

if ($tag_id) {
    echo "Tag ID: $tag_id\n";

    // Rename the tag (update)
    // Set the tag's ID so save() will call update() instead of create()
    $tag = $client->resource(ResourceType::TAG);
    $tag->setRemoteData(['id' => $tag_id]);
    $tag->setValue('name', 'example-tag-renamed');
    $tag->save();

    echo "Tag renamed to 'example-tag-renamed'.\n";

    // Delete the tag
    $tag = $client->resource(ResourceType::TAG);
    $tag->setRemoteData(['id' => $tag_id]);
    $tag->delete();

    echo "Tag deleted.\n";
}
