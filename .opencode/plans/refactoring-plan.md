# Refactoring Plan: `ZammadClient` in drei Rollen zerlegen

## 1. Motivation

Die aktuelle `ZammadClient`-Klasse vereint mehrere widerspruchliche Zustandigkeiten in einer Datei:

- **`RepositoryRegistry` behauptet "single source of truth"** fur Path- und DTO-Mapping — wird aber durch `ZammadClient::aliasMap()` dupliziert.
- **`createClient()` akzeptiert PSR-18-Theorie** (beliebiger `ClientInterface`) — hardcodet aber `new GuzzleClient()` und `new HttpFactory()`.
- **`aliasMap()` / `resolveAlias()` / `__call()`** sind bereits als deprecated markiert, aber noch im Client eingebettet.
- **Impersonation-Methoden** (`setOnBehalfOfUser`, `performOnBehalfOf`, etc.) gehoren als Transport-Concern in den `RequestHandler`, nicht in den Client.
- **`getListKey()`** ist in allen 10 Repository-Implementierungen identisch mit `$this->resourcePath` — 10-fache Redundanz.

Ziel: **Eine Klasse = eine Verantwortung.**

---

## 2. Architektur-Ubersicht

### Vorher

```
ZammadClient (6 Rollen in einer Klasse)
  ├── Factory-Methoden     → withToken, withOAuth2, withBasicAuth, withClient
  ├── Guzzle-Wiring         → createClient, normalizeUrl
  ├── Repository-Locator    → repo, repository, $repos
  ├── Alias-Auflosung       → __call, aliasMap, resolveAlias  [deprecated]
  ├── Impersonation         → setOnBehalfOf, unsetOnBehalfOf, performOnBehalfOf
  └── Handler-Zugriff       → getHandler
```

### Nachher

```
ClientFactory              (Guzzle-Wiring + Factory-Methoden)
  ├── withToken, withOAuth2, withBasicAuth → hardcoden Guzzle
  ├── withClient                           → PSR-18-agnostisch
  ├── createClient (private)               → einzige Guzzle-Stelle
  └── normalizeUrl (private)

ZammadClient               (schlanker Repository-Locator)
  ├── repo, repository, $repos
  └── getHandler

RequestHandlerInterface    (Transport)
  ├── request, get, post, put, delete, getRaw, getLastResponse
  ├── setOnBehalfOfUser, getOnBehalfOfUser
  └── performOnBehalfOf                          [NEU: von ZammadClient hierher]

AbstractRepository         (Basis-Repo)
  └── getListKey() → default $this->resourcePath  [war vorher 10x identisch implementiert]

RepositoryRegistry         (unverandert — single source of truth)
  └── DEFINITIONS: RepoClass → [path, dto]
```

---

## 3. Detaillierte Anderungen

### 3.1 NEU: `src/ClientFactory.php`

```php
<?php

declare(strict_types=1);

namespace ZammadAPIClient;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\NullLogger;
use ZammadAPIClient\Core\ConnectionConfig;
use ZammadAPIClient\Core\RequestHandler;

final class ClientFactory
{
    public const USER_AGENT = 'Zammad API PHP';

    public static function withToken(
        string $url,
        string $token,
        ?ConnectionConfig $config = null,
    ): ZammadClient {
        $config ??= new ConnectionConfig();
        return self::createClient($url, $config, "Token token={$token}");
    }

    public static function withOAuth2(
        string $url,
        string $token,
        ?ConnectionConfig $config = null,
    ): ZammadClient {
        $config ??= new ConnectionConfig();
        return self::createClient($url, $config, "Bearer {$token}");
    }

    public static function withBasicAuth(
        string $url,
        string $user,
        string $pass,
        ?ConnectionConfig $config = null,
    ): ZammadClient {
        $config ??= new ConnectionConfig();
        return self::createClient($url, $config, 'Basic ' . base64_encode("{$user}:{$pass}"));
    }

    public static function withClient(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        string $url,
        ?ConnectionConfig $config = null,
    ): ZammadClient {
        if (!$requestFactory instanceof StreamFactoryInterface) {
            throw new InvalidArgumentException(
                'The factory must implement both RequestFactoryInterface and StreamFactoryInterface.',
            );
        }

        $config ??= new ConnectionConfig();
        $url = self::normalizeUrl($url);

        $handler = new RequestHandler(
            $httpClient,
            $requestFactory,
            $url,
            logger: $config->logger ?? new NullLogger(),
            maxRetries: $config->maxRetries,
        );

        return new ZammadClient($handler);
    }

    private static function normalizeUrl(string $url): string
    {
        $url = rtrim($url, '/');

        if (!str_contains($url, '/api/')) {
            $url .= '/api/v1';
        }

        return $url;
    }

    private static function createClient(
        string $url,
        ConnectionConfig $config,
        string $authHeader,
    ): ZammadClient {
        $url = self::normalizeUrl($url);

        $headers = [
            'User-Agent'    => self::USER_AGENT,
            'Authorization' => $authHeader,
        ];

        $httpClient = new GuzzleClient([
            'headers'         => $headers,
            'verify'          => $config->verifySsl,
            'timeout'         => $config->timeout,
            'connect_timeout' => $config->connectTimeout,
            'allow_redirects' => false,
        ]);

        $handler = new RequestHandler(
            $httpClient,
            new HttpFactory(),
            $url,
            logger: $config->logger ?? new NullLogger(),
            maxRetries: $config->maxRetries,
        );

        return new ZammadClient($handler);
    }
}
```

- **Wichtig**: `createClient()` ist der **einzige Ort im gesamten Codebase**, der Guzzle direkt instantiiert (`new GuzzleClient`, `new HttpFactory`).
- Die Klasse ist `final`, hat nur statische Methoden, keine Properties — reine Factory.
- Alle Ruckgabetypen sind `ZammadClient`.

### 3.2 MODIFY: `src/ZammadClient.php`

**Entfernt (ersatzlos oder verschoben):**

| Methode / Property | Ziel | Grund |
|---|---|---|
| `USER_AGENT` const | `ClientFactory` | Gehort zur Factory |
| `withToken()` | `ClientFactory` | Guzzle-Wiring |
| `withOAuth2()` | `ClientFactory` | Guzzle-Wiring |
| `withBasicAuth()` | `ClientFactory` | Guzzle-Wiring |
| `withClient()` | `ClientFactory` | Gehort konzeptuell zur Factory |
| `createClient()` | `ClientFactory` | Guzzle-Wiring |
| `normalizeUrl()` | `ClientFactory` | URL-Normalisierung ist Factory-Concern |
| `__call()` | **geloscht** | Deprecated; wird in v4.0 entfernt |
| `aliasMap()` | **geloscht** | Deprecated |
| `resolveAlias()` | **geloscht** | Deprecated |
| `setOnBehalfOfUser()` | **geloscht** (war bereits auf Handler) | Transport-Concern |
| `unsetOnBehalfOfUser()` | **geloscht** (war bereits auf Handler) | Transport-Concern |
| `performOnBehalfOf()` | `RequestHandler` (siehe 3.3) | Transport-Concern |

Entfernte Imports:
- `GuzzleHttp\Client as GuzzleClient`
- `GuzzleHttp\Psr7\HttpFactory`
- `InvalidArgumentException` (nur fur `__call` benotigt)
- `Psr\Http\Client\ClientInterface`
- `Psr\Http\Message\RequestFactoryInterface`
- `Psr\Http\Message\StreamFactoryInterface`
- `Psr\Log\NullLogger`
- `ZammadAPIClient\Core\ConnectionConfig`
- `ZammadAPIClient\Core\Contracts\DTOInterface`

**Bleibt (unverandert):**

```php
<?php

declare(strict_types=1);

namespace ZammadAPIClient;

use ZammadAPIClient\Core\AbstractRepository;
use ZammadAPIClient\Core\Contracts\ClientInterface as ZammadClientInterface;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Core\RepositoryRegistry;

final class ZammadClient implements ZammadClientInterface
{
    /** @var array<class-string, object> */
    private array $repos = [];

    public function __construct(
        private RequestHandlerInterface $handler,
    ) {
    }

    public function getHandler(): RequestHandlerInterface
    {
        return $this->handler;
    }

    /**
     * @template T of AbstractRepository
     * @param class-string<T> $repositoryClass
     * @return T
     */
    public function repo(string $repositoryClass): AbstractRepository
    {
        $definition = RepositoryRegistry::definition($repositoryClass);

        return $this->repository($repositoryClass, $definition['path'], $definition['dto']);
    }

    /**
     * @template T of AbstractRepository
     * @param class-string<T>            $repoClass
     * @param class-string<\ZammadAPIClient\Core\Contracts\DTOInterface> $dtoClass
     * @return T
     */
    private function repository(string $repoClass, string $path, string $dtoClass): object
    {
        if (!isset($this->repos[$repoClass])) {
            $this->repos[$repoClass] = new $repoClass($this->handler, $path, $dtoClass);
        }

        /** @var T $repo */
        $repo = $this->repos[$repoClass];

        return $repo;
    }
}
```

**Ergebnis**: ~50 Zeilen (vorher ~210 Zeilen). Die Klasse macht genau eine Sache: `repo()` und `getHandler()`.

### 3.3 MODIFY: `performOnBehalfOf()` auf `RequestHandler` verschieben

#### 3.3a: `src/Core/Contracts/RequestHandlerInterface.php`

**Hinzufugen:**

```php
/**
 * Executes a closure with a temporary impersonation header.
 *
 * The header is set before the callback and restored afterwards.
 *
 * @param int|string $userId User ID, login, or email to impersonate.
 * @param callable   $callback Closure to execute while impersonating.
 * @return mixed Return value of the callback.
 */
public function performOnBehalfOf(int|string $userId, callable $callback): mixed;
```

Vorhandene `setOnBehalfOfUser` und `getOnBehalfOfUser` bleiben unverandert.

#### 3.3b: `src/Core/RequestHandler.php`

**Implementierung hinzufugen:**

```php
public function performOnBehalfOf(int|string $userId, callable $callback): mixed
{
    $previous = $this->onBehalfOfUser;

    $this->onBehalfOfUser = $userId;

    try {
        return $callback();
    } finally {
        $this->onBehalfOfUser = $previous;
    }
}
```

**Hinweis**: Anders als in `ZammadClient` wird hier **kein Argument** an den Callback ubergeben. Der alte Code reichte `$this` (den Client), aber das war fur die tatsachliche Nutzung irrelevant — alle aktuellen Aufrufe verwenden `use`-Closures.

### 3.4 MODIFY: `getListKey()` in `AbstractRepository` als Default

Datei: `src/Core/AbstractRepository.php`

**Anderung**: Aus `abstract` wird eine konkrete Methode mit Default:

```php
// Vorher (abstract):
abstract protected function getListKey(): string;

// Nachher (konkret mit Default):
protected function getListKey(): string
{
    return $this->resourcePath;
}
```

**Begrundung**: In allen 10 Repos ist `getListKey()` identisch mit `$this->resourcePath`. Die Subklassen-Uberschreibungen sind reine Redundanz.

### 3.5 MODIFY: `getListKey()` aus allen Repository-Klassen entfernen

Diese Methode wird aus allen 10 Repository-Dateien ersatzlos gestrichen:

| Datei | Zu entfernender Code |
|---|---|
| `src/Endpoints/Tickets/TicketRepository.php` | `protected function getListKey(): string { return 'tickets'; }` |
| `src/Endpoints/Users/UserRepository.php` | `protected function getListKey(): string { return 'users'; }` |
| `src/Endpoints/Groups/GroupRepository.php` | `protected function getListKey(): string { return 'groups'; }` |
| `src/Endpoints/Organizations/OrganizationRepository.php` | `protected function getListKey(): string { return 'organizations'; }` |
| `src/Endpoints/Links/LinkRepository.php` | `protected function getListKey(): string { return 'links'; }` |
| `src/Endpoints/TicketArticles/TicketArticleRepository.php` | `protected function getListKey(): string { return 'ticket_articles'; }` |
| `src/Endpoints/TicketStates/TicketStateRepository.php` | `protected function getListKey(): string { return 'ticket_states'; }` |
| `src/Endpoints/TicketPriorities/TicketPriorityRepository.php` | `protected function getListKey(): string { return 'ticket_priorities'; }` |
| `src/Endpoints/Tags/TagRepository.php` | `protected function getListKey(): string { return 'tags'; }` |
| `src/Endpoints/TextModules/TextModuleRepository.php` | `protected function getListKey(): string { return 'text_modules'; }` |

### 3.6 NO CHANGES: `src/Core/Contracts/ClientInterface.php`

Das Interface enthalt bereits nur `repo()` und `getHandler()` — es spiegelt bereits das Ziel-Design.

### 3.7 NO CHANGES: `src/Core/RepositoryRegistry.php`

Die Registry bleibt als "single source of truth" unverandert. Mit dem Wegfall von `aliasMap()` im Client gibt es keine Duplikation mehr.

---

## 4. Test-Anderungen

### 4.1 NEU: `test/Unit/ClientFactoryTest.php`

Enthalt die aus `ZammadClientTest` verschobenen Tests:

| Test-Methode | Beschreibung |
|---|---|
| `testWithClientUsesInjectedHttpClient` | Stellt sicher, dass ein injizierter PSR-18-Client verwendet wird |
| `testWithClientThrowsWhenFactoryNotStreamFactory` | Validiert den StreamFactory-Guard |

### 4.2 MODIFY: `test/Unit/ZammadClientTest.php`

**Bleiben (unverandert):**

- `testRepoReturnsMemoizedRepositoryInstance`
- `testRepoThrowsForUnknownRepositoryClass`

**Entfernt (weil getestete Funktion nicht mehr existiert):**

- `testCallResolvesTicketRepository` — `__call` geloscht
- `testCallResolvesUserRepository` — `__call` geloscht
- `testCallMemoizesRepository` — `__call` geloscht
- `testCallThrowsForUnknownResource` — `__call` geloscht
- `testCallResolvesUnderscoreResources` — `__call` geloscht
- `testSetOnBehalfOfUserDelegatesToHandler` — Methode nicht mehr auf Client
- `testUnsetOnBehalfOfUserDelegatesToHandler` — Methode nicht mehr auf Client
- `testPerformOnBehalfOfExecutesCallbackAndResets` — Methode nicht mehr auf Client
- `testPerformOnBehalfOfResetsOnException` — Methode nicht mehr auf Client

**Verschoben nach `ClientFactoryTest`:**

- `testWithClientUsesInjectedHttpClient`
- `testWithClientThrowsWhenFactoryNotStreamFactory`

Nur noch 2 Tests — der Client ist jetzt so dunn, dass wenig zu testen bleibt. Alle anderen Concerns werden an anderer Stelle getestet.

### 4.3 MODIFY: `test/Unit/Core/RequestHandlerTest.php`

Neue Tests fur `performOnBehalfOf()`:

| Test-Methode | Beschreibung |
|---|---|
| `testPerformOnBehalfOfExecutesCallbackAndResets` | Setzt Impersonation, fuhrt Callback aus, stellt Zustand wieder her |
| `testPerformOnBehalfOfResetsOnException` | Stellt Zustand auch bei Exception im Callback wieder her |
| `testPerformOnBehalfOfReturnsCallbackValue` | Ruckgabewert des Callbacks wird durchgereicht |

### 4.4 MODIFY: `test/Integration/Traits/CreatesZammadClient.php`

```php
// Zeilen 30, 34: Vorher
return ZammadClient::withToken($url, $token);
return ZammadClient::withBasicAuth($url, $user, $pass);
// Nachher
return ClientFactory::withToken($url, $token);
return ClientFactory::withBasicAuth($url, $user, $pass);
```

Import hinzufugen: `use ZammadAPIClient\ClientFactory;`

---

## 5. Bridge-Anderungen

### 5.1 `src/Bridge/LaravelServiceProvider.php`

```php
// Zeile 91: Vorher
return ZammadClient::withToken($url, $token);
// Nachher
return ClientFactory::withToken($url, $token);
```

Import hinzufugen: `use ZammadAPIClient\ClientFactory;`

### 5.2 `src/Bridge/SymfonyBundle.php`

```php
// Zeile 86: Vorher
$client = ZammadClient::withToken($url, $token);
// Nachher
$client = ClientFactory::withToken($url, $token);
```

Import hinzufugen: `use ZammadAPIClient\ClientFactory;`

---

## 6. Dokumentation

### 6.1 `examples/cookbook.php`

```php
// Zeilen 29-30: Vorher
$client = $token !== ''
    ? ZammadClient::withToken($url, $token)
    : ZammadClient::withBasicAuth($url, $user, $pass);
// Nachher
$client = $token !== ''
    ? ClientFactory::withToken($url, $token)
    : ClientFactory::withBasicAuth($url, $user, $pass);
```

Import: `use ZammadAPIClient\ClientFactory;`

Zeile 129-132 (Impersonation):
```php
// Vorher:
$client->performOnBehalfOf(1, function () use ($repo, $ticketId) { ... });
// Nachher:
$client->getHandler()->performOnBehalfOf(1, function () use ($repo, $ticketId) { ... });
```

### 6.2 `README.md`

Ersetze `ZammadClient::withToken` → `ClientFactory::withToken` an allen 10 Stellen. Quick-Start-Beispiel:

```php
use ZammadAPIClient\ClientFactory;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;
use ZammadAPIClient\Endpoints\Tickets\TicketRepository;

$client = ClientFactory::withToken('https://zammad.example', 'your-token');
```

### 6.3 `docs/migration-v3.md`

- Zeile 7, 44: `ZammadClient::withToken` → `ClientFactory::withToken`

### 6.4 `docs/migration-v3-examples.md`

- Zeilen 19, 38-40: `ZammadClient::with*` → `ClientFactory::with*`

### 6.5 `docs/alternative-clients.md`

- Zeile 72: `ZammadClient::withToken()` → `ClientFactory::withToken()`

### 6.6 `CHANGELOG.md`

Neue Eintrage unter `## [3.0.0] — unreleased`:

```markdown
### Changed
- **Breaking:** `ZammadClient::withToken()` / `withOAuth2()` / `withBasicAuth()` / `withClient()` → `ClientFactory::with*()`
- **Breaking:** `ZammadClient::performOnBehalfOf()` → `$client->getHandler()->performOnBehalfOf()`
- `getListKey()` in Repository-Klassen nicht mehr abstrakt; Default ist `$this->resourcePath`

### Removed
- **Breaking:** Magic resource accessor (`$client->ticket()`) — ersatzlos entfernt. Nutze `$client->repo(TicketRepository::class)`.
- **Breaking:** `ZammadClient::setOnBehalfOfUser()` / `unsetOnBehalfOfUser()` — nutze `$client->getHandler()->setOnBehalfOfUser()`
- `ZammadClient::aliasMap()`, `resolveAlias()`, `__call()` — deprecated in v3.0, jetzt entfernt
```

---

## 7. Ausfuhrungsreihenfolge

| Schritt | Beschreibung | Dateien | Risiko |
|---|---|---|---|
| 1 | `ClientFactory` erstellen (neue Datei) | 1 new | Kein |
| 2 | `performOnBehalfOf` zu `RequestHandlerInterface` + `RequestHandler` hinzufugen | 2 modify | Gering |
| 3 | `ZammadClient` ausdunnen (factories, aliases, impersonation entfernen) | 1 modify | Mittel |
| 4 | `getListKey()` Default in `AbstractRepository` | 1 modify | Gering |
| 5 | `getListKey()` aus allen 10 Repos entfernen | 10 modify | Gering |
| 6 | `ClientFactoryTest` erstellen | 1 new | Kein |
| 7 | `ZammadClientTest` anpassen | 1 modify | Gering |
| 8 | `RequestHandlerTest` um `performOnBehalfOf`-Tests erganzen | 1 modify | Kein |
| 9 | Framework Bridges updaten | 2 modify | Gering |
| 10 | `CreatesZammadClient` Trait updaten | 1 modify | Gering |
| 11 | `cookbook.php` updaten | 1 modify | Kein |
| 12 | Dokumentation updaten | ~6 modify | Kein |
| 13 | `phpstan analyse src/ --level=max` | — | Prufung |
| 14 | `phpcs` | — | Prufung |
| 15 | `phpunit --testsuite=unit` | — | Prufung |
| 16 | `phpunit --testsuite=integration` | — | Prufung |

---

## 8. Migration Notes fur Endnutzer

```php
// ── Client-Erstellung ──────────────────────────────────
// VORHER:
$client = ZammadClient::withToken($url, $token);
$client = ZammadClient::withBasicAuth($url, $user, $pass);
$client = ZammadClient::withOAuth2($url, $token);
$client = ZammadClient::withClient($http, $factory, $url);

// NACHHER:
$client = ClientFactory::withToken($url, $token);
$client = ClientFactory::withBasicAuth($url, $user, $pass);
$client = ClientFactory::withOAuth2($url, $token);
$client = ClientFactory::withClient($http, $factory, $url);

// ── Impersonation ─────────────────────────────────────
// VORHER:
$client->setOnBehalfOfUser(1);
$client->unsetOnBehalfOfUser();
$client->performOnBehalfOf(1, fn() => doSomething());

// NACHHER:
$client->getHandler()->setOnBehalfOfUser(1);
$client->getHandler()->setOnBehalfOfUser(null);
$client->getHandler()->performOnBehalfOf(1, fn() => doSomething());

// ── Magic Resource Accessor ───────────────────────────
// VORHER:  $client->ticket()->find(1);        // deprecated
// NACHHER: $client->repo(TicketRepository::class)->find(1);  // einziger Weg
```

**Keine Anderungen:**

- `$client->repo(SomeRepository::class)` — unverandert
- `$client->getHandler()->get(...)` / `post(...)` / ... — unverandert
- Alle Repository-Klassen und DTOs — unverandert

---

## 9. Phase 2 (nicht Teil dieses PRs)

1. **Framework Bridges: Repositories im Container registrieren** — statt nur `ZammadClient`, jedes Repository als eigenen Service verfugbar machen.
2. **`RepositoryRegistry` per PHP-Attribut** — `#[Resource('tickets', TicketDTO::class)]` auf der Repo-Klasse statt zentraler Map.
3. **`getListKey()` ganzlich entfernen** — `$this->resourcePath` direkt verwenden.
