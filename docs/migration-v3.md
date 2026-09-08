# Migrating from v2 to v3

## Quick Reference: Before → After

| v2 | v3 |
|---|---|
| `new Client(['url' => ..., 'http_token' => ...])` | `ZammadClient::withToken($url, $token)` |
| `$client->resource(TICKET)` | `$client->ticket()` |
| `$ticket->get(1)` | `$client->ticket()->find(1)` |
| `$ticket->getValue('title')` | `$ticket->title` |
| `$ticket->getValues()` | `$ticket->toArray()` |
| `$ticket->setValue('title', 'x'); $ticket->save()` | `$client->ticket()->patch(1, ['title' => 'x'])` |
| `$ticket->search('term')` | `$client->ticket()->search('term')` |
| `$ticket->all()` | `$client->ticket()->all()` |
| `$ticket->delete()` | `$client->ticket()->delete($id)` |
| `if ($ticket->hasError())` | `catch (NotFoundException\|ValidationException $e)` |

## Why Migrate?

- **Type safety:** `$ticket->title` with IDE autocomplete instead of `$ticket->getValue('title')`
- **Proper exceptions:** `NotFoundException`, `ValidationException`, `RateLimitException` instead of `hasError()`
- **Unit-testable:** Mock the HTTP layer, tests run in milliseconds without a live Zammad instance
- **PSR-compliant:** PSR-18 HTTP client, PSR-17 request factory, PSR-3 logger
- **Generator-based pagination:** Memory-efficient iteration over large datasets

## Step-by-Step Migration

### 1. Update composer.json

```bash
composer require zammad/zammad-api-client-php:^3.0
```

### 2. Replace Client Instantiation

```php
// v2
$client = new \ZammadAPIClient\Client([
    'url'        => 'https://zammad.example/api/v1',
    'http_token' => 'your-token',
]);

// v3
$client = \ZammadAPIClient\ZammadClient::withToken(
    'https://zammad.example',
    'your-token'
);
```

### 3. Replace Resource Access

```php
// v2
$ticket = $client->resource(\ZammadAPIClient\ResourceType::TICKET);
$ticket->get(1);
$title = $ticket->getValue('title');

// v3
$tickets = $client->ticket();
$ticket = $tickets->find(1);
$title = $ticket->title;
```

### 4. Replace Error Handling

```php
// v2
$ticket->get(999);
if ($ticket->hasError()) {
    $error = $ticket->getError();
}

// v3
try {
    $ticket = $client->ticket()->find(999);
} catch (\ZammadAPIClient\Exceptions\NotFoundException $e) {
    $error = $e->getMessage();
}
```

## Adding a New Resource

Register the repository in `RepositoryRegistry::DEFINITIONS` (path + DTO class). No changes to `ZammadClient` are required:

```php
$tickets = $client->ticket();
```
