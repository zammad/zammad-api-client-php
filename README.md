# Zammad API Client for PHP (v3)

PSR-compliant PHP client for the [Zammad](https://zammad.com) REST API. PHP 8.1+.

> **v3 Release Candidate available!** Install via Composer:
> ```bash
> composer require zammad/zammad-api-client-php:^3.0@RC
> ```
> **We want your feedback!**
> [Report a bug](https://github.com/zammad/zammad-api-client-php/issues/new?template=v3-feedback.yml&labels=Zammad+API+Client+v3) ·
> [Start a discussion](https://github.com/zammad/zammad-api-client-php/discussions)

## Quick Start

```php
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
use ZammadAPIClient\ZammadClient;

$client = ZammadClient::withToken('https://zammad.example', 'your-token');

// Fetch
$ticket = $client->repo(TicketRepository::class)->find(1);
echo $ticket->title; // typed property, IDE autocomplete

// Create (customer_id is required on creation; article is optional)
// For production code, resolve priority_id/state_id by name via
// TicketPriorityRepository / TicketStateRepository. See examples/cookbook.php.
$created = $client->repo(TicketRepository::class)->create(new TicketDTO(
    title: 'Hello from v3',
    customer_id: 1,
    group_id: 1,
    priority_id: 2,
    state_id: 1,
    article: [
        'subject' => 'Hello',
        'body'    => 'Message body',
        'type'    => 'note',
    ],
));

// Partial update
$client->repo(TicketRepository::class)->patch($created->id, ['title' => 'Updated']);

// Search
foreach ($client->repo(TicketRepository::class)->search('error') as $ticket) {
    echo $ticket->title;
}
```

## Getting started

### Standalone PHP app

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use ZammadAPIClient\ZammadClient;

$client = ZammadClient::withToken('https://zammad.example', getenv('ZAMMAD_TOKEN'));
```

### Laravel

```bash
# 1. Register the provider in config/app.php (skip if using auto-discovery, Laravel 5.5+)
```
Add `ZammadAPIClient\Bridge\LaravelServiceProvider::class` to `config/app.php`.

```bash
# 2. Publish the default config to config/zammad.php
php artisan vendor:publish --tag=zammad-config
```
Then inject `ZammadClient` via the container.

### Symfony

Register `ZammadAPIClient\Bridge\SymfonyBundle` in `config/bundles.php`.

## Authentication

```php
// Token — sends Authorization: Token token=your-token (Zammad personal access token)
ZammadClient::withToken($url, 'your-token');

// OAuth2 — sends Authorization: Bearer your-oauth-token (OAuth2 access token)
ZammadClient::withOAuth2($url, 'your-oauth-token');

// Basic Auth — sends Authorization: Basic base64(user:pass)
ZammadClient::withBasicAuth($url, 'admin@example.com', 'test');

// Options
ZammadClient::withToken($url, 'your-token',
    new ConnectionConfig(verifySsl: false, maxRetries: 5),
);

// Pass a PSR-3 Logger to log HTTP requests and retries
ZammadClient::withToken($url, 'your-token',
    new ConnectionConfig(logger: $myLogger),
);
```

| ConnectionConfig property | Type | Default | Description |
|---------------------------|------|---------|-------------|
| `maxRetries` | `int` | `3` | Number of retries on HTTP 429 before throwing `RateLimitException` |
| `verifySsl` | `bool` | `true` | Verify SSL certificate of the Zammad server |
| `timeout` | `int` | `30` | Total request timeout in seconds |
| `connectTimeout` | `int` | `10` | Connection timeout in seconds |
| `logger` | `?LoggerInterface` | `null` | PSR-3 Logger for HTTP request/retry logging |

## Examples

The primary example is [`examples/cookbook.php`](examples/README.md)) — nine runnable recipes covering tickets, stateful resources, pagination, error handling, impersonation, and search. Run it against any Zammad instance:

```bash
ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL=http://your-zammad:3000 \
ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TOKEN=your-token \
php examples/cookbook.php
```

> The env vars are named `...UNIT_TESTS...` for historical reasons. They are used by integration tests and the cookbook example. Unit tests (`make test`) need no env vars.

For user and organization CRUD examples, refer to the integration tests in [`test/Integration/`](test/Integration/) (`UserIntegrationTest.php`, `OrganizationIntegrationTest.php`). Side-by-side v2→v3 migration examples are in [`docs/migration-v3-examples.md`](docs/migration-v3-examples.md).

## How to use

This library offers three interaction styles. Choose based on your use case:

| Style | API | Best for |
|-------|-----|----------|
| **Repository + DTOs** (recommended) | `$repo->find()`, `create()`, `patch()`, `delete()` | Type-safe CRUD, IDE autocomplete, explicit intent. Use this by default. |
| **Stateful Resource** | `$repo->resource($id)->save()` / `destroy()` | Interactive editing — mutate properties step by step, only changes are sent. |
| **Raw HTTP** | `$client->getHandler()->get()`, `delete()`, etc. | Calling endpoints that have no dedicated repository. Escape hatch. |
| **Magic accessor** (deprecated) | `$client->ticket()->find(1)` | Triggers `E_USER_DEPRECATED`. Removed in v4. Migrate to `repo()`. |

### Connecting

```php
use ZammadAPIClient\ZammadClient;

// ZammadClient normalizes the URL — /api/v1 is appended automatically
$client = ZammadClient::withToken('https://zammad.example', 'your-token');
```

### Fetching

```php
// Access via typed repository — autocomplete, type-safe
$ticket = $client->repo(TicketRepository::class)->find(1);
$user   = $client->repo(UserRepository::class)->find(1);
$group  = $client->repo(GroupRepository::class)->find(1);
```

### Accessing values

```php
$ticket = $client->repo(TicketRepository::class)->find(1);

echo $ticket->title;          // Typed property, IDE autocomplete
echo $ticket->state_id;       // ?int
echo $ticket->created_at;     // ?DateTimeImmutable

$data = $ticket->toArray();   // All values as array
$id   = $ticket->id;          // Server-assigned ID (null before create)
```

### Creating

```php
$ticket = $client->repo(TicketRepository::class)->create(new TicketDTO(
    title: 'My ticket',
    customer_id: 1,
    group_id: 1,
    priority_id: 2,
    state_id: 1,
    article: [
        'subject' => 'My ticket',
        'body'    => 'First message',
        'type'    => 'note',
    ],
));

echo $ticket->id; // Server-assigned after creation
```

### Updating

```php
$repo = $client->repo(TicketRepository::class);

// Send a DTO — only non-null fields are transmitted
$repo->patch(1, new TicketDTO(title: 'New title', group_id: 1));

// Partial update via array — only supplied fields change
$repo->patch(1, ['title' => 'New title', 'state_id' => 3]);

// Partial update via TicketUpdateDTO — only non-null fields sent
$repo->patch(1, new TicketUpdateDTO(title: 'New title'));
```

### Stateful resource

`$repo->resource($id)` returns a `Resource` wrapper — **not a DTO**. Properties are accessed and mutated via `__get`/`__set` magic (not typed properties), and changes are automatically tracked. `save()` sends only modified fields; `destroy()` sends DELETE.

```php
$repo = $client->repo(TicketRepository::class);

$r = $repo->resource(1);          // Returns Resource, fetches ticket #1
echo $r->title;                   // Reads current title
$r->title = 'Changed';            // Tracks old → new
$r->state_id = 3;                 // Tracks old → new
$r->save();                       // PUT {title, state_id} only
$r->destroy();                    // DELETE
```

Use this for interactive workflows where you read, modify, then write. For single-field changes, prefer `patch()`.

### Choosing the right update method

`patch()` is the only update method. It accepts arrays, `TicketUpdateDTO`, or any DTO (via `toArray()`). Zammad uses HTTP PUT for all updates and merges the payload with the existing resource. Null values are excluded from all request bodies, so absent fields are never overwritten.

| Signature | What it sends | Use case |
|-----------|---------------|----------|
| `patch($id, $array)` | Only the explicit array keys | Change one or two known fields. **Safest.** |
| `patch($id, $updateDto)` | Only the non-null DTO fields | IDE autocomplete on the mutable fields. |
| `patch($id, $dto)` | All non-null properties of the DTO (`toArray()`) | Replace multiple fields using a full DTO. |
| `resource($id)->save()` | Only actually changed fields (tracked) | Interactive editing with change tracking. |

```php
$repo = $client->repo(TicketRepository::class);

// Array — simplest for ad-hoc changes
$repo->patch(1, ['title' => 'New title', 'state_id' => 3]);

// TicketUpdateDTO — type-safe, IDE-friendly
$repo->patch(1, new TicketUpdateDTO(title: 'New title'));

// Full DTO — send a modified TicketDTO
$ticket = $repo->find(1);
$repo->patch(1, new TicketDTO(
    title: $ticket->title,
    group_id: 2, // changed from original
));

// Stateful resource — tracks property changes
$r = $repo->resource(1);
$r->title = 'Changed';
$r->state_id = 3;
$r->save(); // Sends only {title, state_id}
```

### Deleting

All repositories expose a `delete()` method. Repositories implementing `DeletableInterface` perform the actual API call. Other repositories throw a `BadMethodCallException` — catchable, unlike a fatal error.

| Repository | `delete()` | Notes |
|-----------|:----------:|-------|
| `TicketRepository` | ✓ | |
| `UserRepository` | ✓ | |
| `GroupRepository` | ✓ | |
| `OrganizationRepository` | ✓ | |
| `TextModuleRepository` | ✓ | |
| `TagRepository` | exception | Throws `BadMethodCallException`; use `add()` / `remove()` |
| `LinkRepository` | exception | Throws `BadMethodCallException`; use `add()` / `remove()` |
| `TicketArticleRepository` | exception | Zammad API does not allow article deletion |
| `TicketStateRepository` | exception | System resource, read-only |
| `TicketPriorityRepository` | exception | System resource, read-only |

```php
$client->repo(TicketRepository::class)->delete(1);
$client->repo(UserRepository::class)->delete(1);
```

### Searching

```php
$repo = $client->repo(TicketRepository::class);

// Full-text search — returns a lazy Generator (page by page)
// Use foreach directly; count()/array access requires iterator_to_array()
foreach ($repo->search('some text') as $ticket) {
    echo $ticket->title;
}

// Field-specific search
foreach ($repo->search('title:Error AND priority_id:1') as $ticket) {
    echo $ticket->number;
}

// PaginatedList — count(), totalCount(), page navigation
$list = $repo->searchList('error', ['per_page' => 25]);
echo $list->totalCount();
$list->page(2);
$list->each(function ($t) { echo $t->title; });
```

### Listing all

```php
$repo = $client->repo(TicketRepository::class);

// Lazy Generator — pages fetched on demand, memory-efficient
// Use in foreach; need count? Use list() for PaginatedList instead.
foreach ($repo->all() as $ticket) {
    echo $ticket->title;
}

// PaginatedList — count(), page navigation, each() callback
$list = $repo->list();
echo $list->count();                    // Items on current page
$list->page(2);                         // Jump to page 2
$list->pageNext();                      // Next page
$list->each(function ($t) { echo $t->title; });
```

### Ticket articles

```php
$repo = $client->repo(TicketArticleRepository::class);

// All articles for a ticket (paginated)
foreach ($repo->getForTicket(1) as $article) {
    echo $article->body;
}

// Download raw attachment content
$binary = $repo->getAttachmentContent(
    ticketId: 1, articleId: 5, attachmentId: 23,
);
```

### Tags

```php
$repo = $client->repo(TagRepository::class);

$repo->add('Ticket', $ticketId, 'urgent');
$repo->remove('Ticket', $ticketId, 'urgent');

foreach ($repo->all(['object' => 'Ticket', 'o_id' => $ticketId]) as $tag) {
    echo $tag->value;
}

$results = $repo->tagSearch('urg'); // Autocomplete
```

### CSV import

```php
$csv = file_get_contents('users.csv');
$result = $client->repo(UserRepository::class)->import($csv);           // Returns import summary array
$result = $client->repo(OrganizationRepository::class)->import($csv);   // Returns import summary array
$client->repo(TextModuleRepository::class)->import($csv);               // Returns import summary array
```

All `import()` methods return an `array` — the Zammad API response containing import statistics (rows processed, skipped, errors).
CSV format follows Zammad's import specification (header row with field names matching API field names).

## Error Handling

All errors are typed exceptions:

```php
use ZammadAPIClient\Exceptions\{
    AuthenticationException,
    ForbiddenException,
    NotFoundException,
    ValidationException,
    RateLimitException,
    ServerErrorException,
    NetworkException,
};

try {
    $client->repo(TicketRepository::class)->find(999999);
} catch (NotFoundException $e) {
    echo $e->getMessage();          // "Resource not found: tickets/999999"
} catch (ValidationException $e) {
    print_r($e->errors);            // Per-field validation messages
} catch (AuthenticationException $e) {
    // Invalid credentials (401)
} catch (ForbiddenException $e) {
    // Valid credentials but insufficient permissions (403)
} catch (RateLimitException $e) {
    echo $e->retryAfterSeconds;     // Auto-retried, thrown on exhaustion (429)
} catch (ServerErrorException $e) {
    // Server error (5xx)
} catch (NetworkException $e) {
    // DNS, timeout, connection refused
}
```

| Exception | HTTP | Auto-retry | Properties |
|-----------|------|:----------:|------------|
| `AuthenticationException` | 401 | no | `$e->getMessage()` |
| `ForbiddenException` | 403 | no | `$e->getMessage()` |
| `NotFoundException` | 404 | no | `$e->getMessage()` |
| `ValidationException` | 422 | no | `$e->errors` (array, per-field) |
| `RateLimitException` | 429 | yes | `$e->retryAfterSeconds` |
| `ServerErrorException` | 5xx | no | `$e->getMessage()` |
| `NetworkException` | — | no | DNS, timeout, connection refused |

## Data Transfer Objects (DTOs)

Each repository returns typed DTOs. Below are the fields for each DTO. Fields marked **yes** have no default and are required in the constructor. Fields marked **creation** are nullable in the type signature but required by the Zammad API when creating a new resource — omitting them will result in a `ValidationException` (422).

The `id` field is available both as a property (`$dto->id`) and a convenience method (`$dto->id()`). Both return the same server-assigned ID; prefer the property for readability.

### TicketDTO

| Field | Type | Required | Notes |
|-------|------|:--------:|-------|
| `title` | `string` | yes | Subject line of the ticket |
| `group_id` | `?int` | — | Group responsible for the ticket |
| `priority_id` | `?int` | — | References TicketPriority; resolve by name via `TicketPriorityRepository` |
| `state_id` | `?int` | — | References TicketState; resolve by name via `TicketStateRepository` |
| `organization_id` | `?int` | — | Derived from customer's organization |
| `customer_id` | `?int` | **creation** | End-user who submitted the ticket (Zammad requires this on create) |
| `owner_id` | `?int` | — | Agent assigned to the ticket |
| `number` | `?string` | — | Human-readable ticket number (read-only) |
| `id` | `?int` | — | Server-assigned (null before creation) |
| `pending_time` | `?DateTimeImmutable` | — | ISO 8601 datetime for pending states |
| `article` | `?array` | — | Optional initial article. Array shape: `{ subject: string, body: string, type: string, internal?: bool, content_type?: string, ... }`. Use `TicketArticleType` enum constants (e.g. `TicketArticleType::Note->value`) for type-safe `type` values. |
| `created_at` | `?DateTimeImmutable` | — | Server-assigned (read-only) |
| `updated_at` | `?DateTimeImmutable` | — | Server-assigned (read-only) |
| `customFields` | `array` | — | Zammad custom fields (`string => mixed`). Named camelCase per Zammad API convention.

### TicketUpdateDTO

Used with `patch()` for partial ticket updates. Only non-null fields are sent to the API.

| Field | Type | Notes |
|-------|------|-------|
| `title` | `?string` | |
| `state_id` | `?int` | |
| `priority_id` | `?int` | |
| `group_id` | `?int` | |
| `owner_id` | `?int` | |
| `customer_id` | `?int` | |
| `note` | `?string` | Adds an internal note (article type 'note') on update |
| `pending_time` | `?DateTimeImmutable` | ISO 8601 datetime for pending states |

```php
// Example: reassign ticket and leave an internal note
$client->repo(TicketRepository::class)->patch(42, new TicketUpdateDTO(
    owner_id: 7,
    note: 'Reassigned from support queue.',
));
```

### UserDTO

| Field | Type | Required | Notes |
|-------|------|:--------:|-------|
| `login` | `?string` | — | Unique username |
| `email` | `?string` | — | Primary email address |
| `firstname` | `?string` | — | |
| `lastname` | `?string` | — | |
| `phone` | `?string` | — | |
| `organization_id` | `?int` | — | Primary organization |
| `organization_ids` | `?array` | — | Array of secondary organization IDs |
| `role_ids` | `?array` | — | Array of role IDs (e.g. `[2]` for Agent) |
| `active` | `?bool` | — | Whether the user account is active |
| `id` | `?int` | — | Server-assigned |
| `created_at` | `?DateTimeImmutable` | — | Read-only |
| `updated_at` | `?DateTimeImmutable` | — | Read-only |
| `customFields` | `array` | — | |

### OrganizationDTO

| Field | Type | Required | Notes |
|-------|------|:--------:|-------|
| `name` | `string` | yes | Display name |
| `note` | `?string` | — | |
| `active` | `?bool` | — | |
| `id` | `?int` | — | Server-assigned |
| `created_at` | `?DateTimeImmutable` | — | Read-only |
| `updated_at` | `?DateTimeImmutable` | — | Read-only |
| `customFields` | `array` | — | |

### GroupDTO

| Field | Type | Required | Notes |
|-------|------|:--------:|-------|
| `name` | `string` | yes | Display name |
| `note` | `?string` | — | |
| `active` | `?bool` | — | |
| `id` | `?int` | — | Server-assigned |
| `created_at` | `?DateTimeImmutable` | — | Read-only |
| `updated_at` | `?DateTimeImmutable` | — | Read-only |
| `customFields` | `array` | — | |

### TicketArticleDTO

| Field | Type | Notes |
|-------|------|-------|
| `ticket_id` | `?int` | Parent ticket |
| `type` | `?string` | Channel type: `TicketArticleType::Note->value` (`'note'`), `Email` (`'email'`), `Phone` (`'phone'`), `Sms` (`'sms'`), `Web` (`'web'`) |
| `body` | `?string` | Message content |
| `content_type` | `?string` | MIME type: `'text/plain'` or `'text/html'` |
| `subject` | `?string` | Subject line for email-type articles |
| `from` | `?string` | Sender address/name |
| `to` | `?string` | Recipient address |
| `cc` | `?string` | CC address |
| `internal` | `?bool` | Whether it's an internal note (hidden from customer) |
| `in_reply_to` | `?string` | Message-ID for threading |
| `reply_to` | `?string` | Reply-To address |
| `message_id` | `?string` | Message-ID of this article |
| `origin_by_id` | `?int` | User who created the article (for impersonation) |
| `sender` | `?string` | Read-only: `'Customer'`, `'Agent'`, etc. |
| `type_id` | `?int` | Read-only |
| `sender_id` | `?int` | Read-only |
| `created_by_id` | `?int` | Read-only |
| `updated_by_id` | `?int` | Read-only |
| `created_by` | `?string` | Read-only |
| `updated_by` | `?string` | Read-only |
| `time_unit` | `?float` | Time accounting (minutes) |
| `attachments` | `?array` | Array of `{filename, data (base64), mime-type?}` |
| `id` | `?int` | Server-assigned |
| `created_at` | `?DateTimeImmutable` | Read-only |
| `updated_at` | `?DateTimeImmutable` | Read-only |

### TicketStateDTO

| Field | Type | Required | Notes |
|-------|------|:--------:|-------|
| `name` | `string` | yes | Display label (e.g. `'open'`, `'closed'`) |
| `state_type_id` | `?int` | — | Determines Zammad's automation behaviour |
| `note` | `?string` | — | |
| `active` | `?bool` | — | |
| `id` | `?int` | — | Server-assigned |
| `created_at` | `?DateTimeImmutable` | — | Read-only |
| `updated_at` | `?DateTimeImmutable` | — | Read-only |

### TicketPriorityDTO

| Field | Type | Required | Notes |
|-------|------|:--------:|-------|
| `name` | `string` | yes | Display label (e.g. `'2 normal'`, `'3 high'`) |
| `note` | `?string` | — | |
| `active` | `?bool` | — | |
| `id` | `?int` | — | Server-assigned |
| `created_at` | `?DateTimeImmutable` | — | Read-only |
| `updated_at` | `?DateTimeImmutable` | — | Read-only |

### TextModuleDTO

| Field | Type | Required | Notes |
|-------|------|:--------:|-------|
| `name` | `string` | yes | Display name |
| `keywords` | `?string` | — | Space-separated search keywords |
| `content` | `?string` | — | Template body with optional `#{...}` variables |
| `note` | `?string` | — | |
| `active` | `?bool` | — | |
| `id` | `?int` | — | Server-assigned |
| `created_at` | `?DateTimeImmutable` | — | Read-only |
| `updated_at` | `?DateTimeImmutable` | — | Read-only |

### TagDTO

| Field | Type | Notes |
|-------|------|-------|
| `id` | `?int` | Tag-assignment ID |
| `object` | `?string` | Object class name (e.g. `'Ticket'`) |
| `o_id` | `?int` | Numeric ID of the tagged object |
| `value` | `?string` | Tag string (e.g. `'urgent'`, `'bug'`) |

### LinkDTO

| Field | Type | Notes |
|-------|------|-------|
| `id` | `?int` | Server-assigned |
| `link_type_id` | `?int` | |
| `link_type` | `?string` | `'normal'`, `'parent'`, or `'child'` |
| `link_object_source` | `?string` | Source object type |
| `link_object_source_value` | `?int` | Source object ID |
| `link_object_target` | `?string` | Target object type |
| `link_object_target_value` | `?int` | Target object ID |
| `created_at` | `?DateTimeImmutable` | Read-only |
| `updated_at` | `?DateTimeImmutable` | Read-only |

## Impersonation

```php
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;

// Temporary — auto-cleanup via finally
// Accepts user ID (int), login, or email (string)
$client->performOnBehalfOf(1, fn() => $client->repo(TicketRepository::class)->find(42));
$client->performOnBehalfOf('agent@example.com', fn() => $client->repo(TicketRepository::class)->find(42));

// Persistent — same parameter types
$client->setOnBehalfOfUser(1);
// ... all subsequent requests act as user #1 ...
$client->unsetOnBehalfOfUser();
```

## Development

```bash
composer install
make test             # Unit tests (<1s, no Docker, no Zammad needed)
make test-integration # Integration tests (requires a running Zammad instance)
```

### Unit tests

No environment variables needed. Unit tests mock the HTTP layer and run fully isolated.

### Integration tests

These require a running Zammad instance and authentication credentials:

| Variable | Required | Default | Description |
|---|---|---|---|
| `ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL` | Yes | `http://localhost:3000` | Zammad server URL (without /api/v1) |
| `ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TOKEN` | No | — | Token authentication (preferred) |
| `ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_USERNAME` | No* | — | Username for basic auth |
| `ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_PASSWORD` | No* | — | Password for basic auth |

\* Either `ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TOKEN` or `USERNAME`+`PASSWORD` must be set.

## Migration from v2

See [docs/migration-v3-examples.md](docs/migration-v3-examples.md) for side-by-side code examples.
v2 reference documentation is preserved in [docs/v2-reference.md](docs/v2-reference.md).

| v2 | v3 |
|----|----|
| `new Client(['url' => ..., 'http_token' => ...])` | `ZammadClient::withToken($url, ...)` |
| `$client->resource(TICKET)->get(1)` | `$client->repo(TicketRepository::class)->find(1)` |
| `$ticket->getValue('title')` | `$ticket->title` |
| `$ticket->getValues()` | `$ticket->toArray()` |
| `$ticket->setValue('title', 'x'); $ticket->save()` | `$client->repo(TicketRepository::class)->patch(1, ['title' => 'x'])` |
| `if ($ticket->hasError()) { $ticket->getError(); }` | `catch (NotFoundException $e) { $e->getMessage(); }` |
| `$client->resource(TICKET)->search('term')` | `$client->repo(TicketRepository::class)->search('term')` |
| `$client->resource(TICKET)->all()` | `$client->repo(TicketRepository::class)->all()` |
| `$ticket->delete()` | `$client->repo(TicketRepository::class)->delete($id)` |
| `$client->resource(TAG)->add($ticketId, 'tag', 'Ticket')` | `$client->repo(TagRepository::class)->add('Ticket', $ticketId, 'tag')` *(order changed)* |

## License

AGPL-3.0 or MIT.
