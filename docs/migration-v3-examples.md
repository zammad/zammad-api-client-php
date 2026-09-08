# Migration from v2 to v3 — Side-by-Side Examples

## Connecting

**v2:**
```php
use ZammadAPIClient\Client;

$client = new Client([
    'url'        => 'https://zammad.example/api/v1',
    'http_token' => 'your-token',
]);
```

**v3:**
```php
use ZammadAPIClient\ZammadClient;

$client = ZammadClient::withToken(
    'https://zammad.example',
    'your-token',
);
```

---

## Authentication Methods

**v2:**
```php
new Client(['url' => $url, 'http_token' => '...']);      // Token
new Client(['url' => $url, 'oauth2_token' => '...']);     // OAuth2
new Client(['url' => $url, 'username' => '...', 'password' => '...']); // Basic
```

**v3:**
```php
ZammadClient::withToken($url, '...');                          // Token
ZammadClient::withOAuth2($url, '...');                         // OAuth2
ZammadClient::withBasicAuth($url, '...', '...');               // Basic
```

---

## Fetching by ID

**v2:**
```php
$ticket = $client->resource(ResourceType::TICKET)->get(1);
$title = $ticket->getValue('title');
```

**v3:**
```php
$ticket = $client->ticket()->find(1);
$title = $ticket->title;
```

---

## Accessing Values

**v2:**
```php
$title  = $ticket->getValue('title');
$data   = $ticket->getValues();
```

**v3:**
```php
$title  = $ticket->title;
$data   = $ticket->toArray();
```

---

## Creating

**v2:**
```php
$ticket = $client->resource(ResourceType::TICKET);
$ticket->setValue('title', 'My ticket');
$ticket->setValue('group_id', 1);
$ticket->save();
```

**v3:**
```php
$ticket = $client->ticket()->create(new TicketDTO(
    title: 'My ticket',
    group_id: 1,
));
```

---

## Updating

**v2:**
```php
$ticket = $client->resource(ResourceType::TICKET)->get(1);
$ticket->setValue('title', 'Updated');
$ticket->setValue('state_id', 3);
$ticket->save();
```

**v3:**
```php
// Via array
$client->ticket()->patch(1, ['title' => 'Updated']);

// Via TicketUpdateDTO
$client->ticket()->patch(1, new TicketUpdateDTO(
    title: 'Updated',
));

// Via TicketDTO (same behavior — all non-null fields are sent)
$client->ticket()->patch(1, new TicketDTO(
    title: 'Updated',
    state_id: 3,
));
```

---

## Stateful Resource (Change Tracking)

**v2:**
```php
$ticket = $client->resource(ResourceType::TICKET)->get(1);
$ticket->setValue('title', 'New');
$ticket->setValue('state_id', 3);
$ticket->save(); // Sends all values back to Zammad
```

**v3:**
```php
$resource = $client->ticket()->resource(1);
$resource->title = 'New';
$resource->state_id = 3;
$resource->save(); // Sends only {title, state_id}

// Delete
$resource->destroy();
```

---

## Deleting

**v2:**
```php
$ticket = $client->resource(ResourceType::TICKET)->get(1);
$ticket->delete();
```

**v3:**
```php
$client->ticket()->delete(1);
```

---

## Searching

**v2:**
```php
$tickets = $client->resource(ResourceType::TICKET)->search('some text');
if (!is_array($tickets)) {
    print $tickets->getError();
} else {
    foreach ($tickets as $t) {
        echo $t->getValue('title');
    }
}
```

**v3:**
```php
try {
    foreach ($client->ticket()->search('some text') as $ticket) {
        echo $ticket->title;
    }
} catch (NotFoundException $e) {
    echo $e->getMessage();
}
```

---

## Listing All

**v2:**
```php
$tickets = $client->resource(ResourceType::TICKET)->all();
```

**v3:**
```php
foreach ($client->ticket()->all() as $ticket) {
    echo $ticket->title;
}
```

---

## Pagination

**v2:**
```php
$page = $client->resource(ResourceType::TICKET)->all(1, 25);
```

**v3:**
```php
$list = $client->ticket()->list(['per_page' => 25]);
$list->page(1);      // First page
$list->page(2);      // Second page
$list->pageNext();   // Next page
$list->pagePrev();   // Previous page
$list->each(function ($t) { echo $t->title; });
```

---

## Error Handling

**v2:**
```php
$ticket = $client->resource(ResourceType::TICKET)->get(999);
if ($ticket->hasError()) {
    echo $ticket->getError();
}
```

**v3:**
```php
try {
    $ticket = $client->ticket()->find(999);
} catch (NotFoundException $e) {
    echo $e->getMessage();        // "Resource not found: tickets/999"
} catch (ValidationException $e) {
    print_r($e->errors);          // Per-field validation messages
} catch (AuthenticationException $e) {
    // Invalid credentials
} catch (RateLimitException $e) {
    echo $e->retryAfterSeconds;   // Auto-retried, only thrown on exhaustion
} catch (ServerErrorException $e) {
    // 5xx server error
} catch (NetworkException $e) {
    // DNS, timeout, connection refused
}
```

---

## Ticket Articles

**v2:**
```php
$ticket = $client->resource(ResourceType::TICKET)->get(1);
$articles = $ticket->getTicketArticles();
```

**v3:**
```php
foreach ($client->ticketArticle()->getForTicket(1) as $article) {
    echo $article->body;
}
```

---

## Ticket Article Attachments

**v2:**
```php
$ticket_article = $client->resource(ResourceType::TICKET_ARTICLE);
$content = $ticket_article->getAttachmentContent(23);
```

**v3:**
```php
$content = $client->ticketArticle()->getAttachmentContent(
    ticketId: 1,
    articleId: 5,
    attachmentId: 23,
);
```

---

## Tags

**v2:**
```php
$client->resource(ResourceType::TAG)->add($ticketId, 'urgent', 'Ticket');
$client->resource(ResourceType::TAG)->remove($ticketId, 'urgent', 'Ticket');

$tag = $client->resource(ResourceType::TAG)->get($ticketId, 'Ticket');
$tags = $tag->getValue('tags');
```

**v3:**
```php
$client->tag()->add('Ticket', $ticketId, 'urgent');
$client->tag()->remove('Ticket', $ticketId, 'urgent');

foreach ($client->tag()->all(['object' => 'Ticket', 'o_id' => $ticketId]) as $tag) {
    echo $tag->value;
}

$results = $client->tag()->tagSearch('urg');
```

---

## CSV Import

**v2:**
```php
$csv = file_get_contents('users.csv');
$client->resource(ResourceType::USER)->import($csv);
```

**v3:**
```php
$csv = file_get_contents('users.csv');
$client->user()->import($csv);
```

---

## Impersonation (On-Behalf-Of)

**v2:**
```php
$client->setOnBehalfOfUser('myuser');
// ... API calls ...
$client->unsetOnBehalfOfUser();
```

**v3:**
```php
// Temporary (auto-cleanup on callback return or exception)
$client->performOnBehalfOf(1, function () use ($client) {
    $client->ticket()->find(42);
});

// Persistent
$client->setOnBehalfOfUser(1);
// ... API calls ...
$client->unsetOnBehalfOfUser();
```

> **Breaking:** v2 used a username string (`'myuser'`). v3 requires a **numeric user ID**. Use `$client->user()->search('login:myuser')` to resolve a username to an ID if needed.

---

## Resource Access — Typed Accessors

**v3 (recommended — typed accessors with IDE autocomplete):**
```php
$client->ticket()->find(1);
$client->user()->find(1);
$client->organization()->find(1);
$client->group()->find(1);
$client->ticketArticle()->getForTicket(1);
$client->ticketState()->all();
$client->ticketPriority()->all();
$client->tag()->add('Ticket', 1, 'urgent');
$client->textModule()->find(1);
$client->link()->find(1);
```

The underlying `repo()` method is `@internal` — prefer the typed accessors.
