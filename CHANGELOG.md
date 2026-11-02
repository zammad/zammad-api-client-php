# Changelog

## [3.2.0] — unreleased

### Added
- `Auth` — credentials as a transport-independent value: `Auth::token()`, `Auth::oauth2()`, `Auth::basic()`. The constructor is private, so the set of valid schemes is closed by construction; empty tokens and usernames are rejected on creation instead of surfacing as a 401 later
- `ZammadClient::create(string $url, Auth $auth, ?ConnectionConfig $config = null)` — one-line entry point for the default (Guzzle) transport
- `RequestHandler` accepts an optional `auth:` argument and applies the `Authorization` header per request
- `ConnectionConfig::$allowInsecureHttp` / `RequestHandler`'s `allowInsecureHttp:` argument — opt out of the cleartext-credential check described below

### Changed
- Authentication no longer depends on the HTTP client supporting client-level default headers. It is applied by `RequestHandler` when composing each request, so any PSR-18 transport authenticates the same way — the hand-written `AuthMiddleware` that the docs previously required for Symfony's `Psr18Client` is obsolete
- **Breaking:** credentials are no longer sent in cleartext. Constructing a client with an `Auth` and a plain `http://` URL whose host is not loopback now throws an `InvalidArgumentException` instead of putting the token on the wire for anyone on the path to read. Loopback is exempt — `localhost`, any `.localhost` name, the whole `127.0.0.0/8` range and `::1` — so every default this project ships (`http://localhost:3000` in the cookbook, `http://127.0.0.1:8098` in both bridges) is unaffected, as is any `https://` URL. **Who is affected:** anyone pointing the client at a remote Zammad over plain HTTP, e.g. an app server talking to an internal instance or a container reaching `http://zammad-nginx:3000`. Switch the URL to `https://`, or, if the connection is already protected by other means (VPN, service mesh, SSH tunnel), opt out with `new ConnectionConfig(allowInsecureHttp: true)` — or `allowInsecureHttp: true` when building a `RequestHandler` directly. The check keys on the `Auth` it is given, so an `Authorization` header that your own PSR-18 client adds stays your responsibility ([#173](https://github.com/zammad/zammad-api-client-php/pull/173))
- `GuzzleClientFactory` takes `(string $url, Auth $auth, ?ConnectionConfig $config)` and no longer sets an `Authorization` default header on the Guzzle client. Its constructor was private before, so no caller is affected
- The `@deprecated` marker on `DTOInterface::id()`'s trait implementation is withdrawn. It announced a removal in 4.0 that was never planned, was never mentioned in the 3.0.0 changelog that introduced it, and contradicted both the interface — which requires the method and is not deprecated — and the library's own use of it in `Resource`. Reading `$dto->id` directly remains the more readable form
- Documentation: corrected examples that referenced `ZammadClient::withToken()` and the impersonation methods removed in 3.0.0 ([#168](https://github.com/zammad/zammad-api-client-php/issues/168))
- **Fixed:** the Laravel override and custom-middleware examples in the custom HTTP client guide built a client with no credentials at all, so every request would have failed with HTTP 401. They now pass an `Auth` to the `RequestHandler`
- **Fixed:** `SymfonyBundle` registered the client with `ContainerBuilder::set()`, which stores a live instance rather than a definition. Instances set that way do not survive container compilation, so any service injecting `ZammadClient` failed the build with *"has a dependency on a non-existent service"* — the bundle never worked in a compiled container. It now registers a lazy `Definition` with `ZammadClient::create()` as its factory and an inlined `Auth` definition, so nothing is instantiated at compile time and the credentials are resolved from `%env(ZAMMAD_TOKEN)%` at runtime instead of being written into `var/cache`
- **Fixed:** the password parameters of `Auth::basic()` and `GuzzleClientFactory::withBasicAuth()` are marked `#[\SensitiveParameter]`. An empty username throws from inside `Auth::basic()`, so with PHP's default `zend.exception_ignore_args=0` the password appeared verbatim in the stack trace. The attribute is honoured from PHP 8.2 on and inert on 8.1
- **Fixed:** the Symfony section of the custom HTTP client guide offered the `create()` definition and a custom PSR-18 stack in one `services:` block, with the custom stack under its own `app.zammad_client` ID. Autowiring resolves a `ZammadClient` type-hint to the `ZammadAPIClient\ZammadClient` ID, so anyone copying that block got the Guzzle client and a dead custom stack. The two are now separate, clearly alternative blocks, both registering the class ID — the same shape `SymfonyBundle` uses
- **Fixed:** the Slim recipe (`examples/cookbook/10-slim.php`) told readers to create a `.env` file and then read `$_ENV` without loading one — and `$_ENV` is empty altogether unless `variables_order` includes `E`. It now uses `getenv()`, like every other recipe
- **Fixed:** the v3 migration guide's `composer require` line allowed `^3.0`, while the client instantiation shown directly below it uses `ZammadClient::create()`, which only exists from 3.2.0
- **Fixed:** `LaravelServiceProvider` read `ZAMMAD_URL` / `ZAMMAD_TOKEN` with `env()` from inside the container binding, i.e. at the moment the client was resolved. Under `php artisan config:cache` Laravel skips the `LoadEnvironmentVariables` bootstrapper altogether, so `.env` is never loaded and such a call returns its default — an application that kept its token in `.env` and never ran `vendor:publish` therefore worked in development and failed in production with *"API token must not be empty."*. `register()` now merges the packaged `config/zammad.php` via `mergeConfigFrom()`, which leaves the single `env()` call in the one file `config:cache` actually freezes. **Who is affected:** anyone setting an explicitly empty `url` or `token` in a published `config/zammad.php` while relying on the environment to fill it in — an explicit configuration value now wins over the environment instead of being treated as unset. The built-in URL default still applies to an empty value ([#169](https://github.com/zammad/zammad-api-client-php/issues/169))
- **Fixed:** the provider passed `mixed` configuration values straight into `ZammadClient::create()` and `Auth::token()`. Under `declare(strict_types=1)` a non-string value in a published `config/zammad.php` surfaced as a `TypeError` raised from inside the client, naming the client rather than the config file that had to be fixed. Values are now narrowed before use ([#169](https://github.com/zammad/zammad-api-client-php/issues/169))
- **Fixed:** `Cast::dateTime()` caught `DateMalformedStringException`, a class that only exists from PHP 8.3 on. The library supports PHP 8.1 and 8.2, where `new DateTimeImmutable()` throws a plain `Exception` for an unparsable value — the catch never matched and the exception escaped, contradicting both the method's documented `null` return and the class contract that these coercions never throw. It now catches `Exception`, which on 8.3 and later is the same thing. **Who is affected:** anyone on PHP 8.1 or 8.2 whose Zammad returns a timestamp the parser rejects ([#169](https://github.com/zammad/zammad-api-client-php/issues/169))
- Static analysis runs on PHPStan 2.x in all three projects, and the 21 findings that the newer analyser surfaced are corrected rather than suppressed — no baseline file is introduced. Most came from one cause: `get_object_vars()` is typed `array<array-key, mixed>`, because a numeric property name decodes to an integer key, so `toArray()` and `toPatchArray()` never actually delivered the `array<string, mixed>` they promised ([#176](https://github.com/zammad/zammad-api-client-php/issues/176))
- **Breaking:** the decoded-body returns of `RequestHandlerInterface` (`request()`, `get()`, `post()`, `put()`, `delete()`) are documented as `array<array-key, mixed>` instead of `array<string, mixed>`. That is what they have always returned: Zammad answers search endpoints with a top-level JSON array, which decodes to integer keys, and `ResponseParser` has handled that shape all along. The new `ResponseParser::fields()` narrows a body to the string-keyed shape where a single resource is expected. **Who is affected:** only code that runs static analysis against these methods and relied on the narrower declaration; there is no signature or runtime change ([#176](https://github.com/zammad/zammad-api-client-php/issues/176))
- **Breaking:** `PaginatedList::offsetGet()` and `offsetExists()` no longer return `null` / `false` for a non-integer offset. The class declares `ArrayAccess<int, T>`, so those guards were unreachable under the declared types; with them gone, `$list['some-string']` now raises a `TypeError` rather than silently yielding nothing. **Who is affected:** anyone indexing a `PaginatedList` with a non-integer offset, which was never part of the contract ([#176](https://github.com/zammad/zammad-api-client-php/issues/176))
- **Fixed:** header values passed through `$options['headers']` reached PSR-7's `withHeader()` unchecked, so a non-string value surfaced as an `InvalidArgumentException` raised from inside the transport layer rather than naming the caller's own input. Scalars are now coerced to strings and arrays mapped element-wise ([#176](https://github.com/zammad/zammad-api-client-php/issues/176))
- **Fixed:** the Symfony bundle fell back to `%env(ZAMMAD_TOKEN)%` with no default, so an application that registered the bundle without setting that variable got Symfony's `EnvNotFoundException: Environment variable not found: "ZAMMAD_TOKEN"` when it first resolved the client — an error about Symfony's env layer rather than about the missing credential. It now falls back through a `zammad.default_token` parameter, the same shape the URL already used, so the failure is the library's own `InvalidArgumentException: API token must not be empty.` — identical to what `LaravelServiceProvider` has always done. **Who is affected:** anyone relying on the previous exception type or message in a `catch` block or a health check ([#169](https://github.com/zammad/zammad-api-client-php/issues/169))
- Both framework bridges are verifiable by contributors. They are tested from separate Composer projects under `test/Bridge/Symfony/` and `test/Bridge/Laravel/`, each with its own `vendor/`, its own PHPStan run at level max and its own PHPUnit configuration. The Symfony suite loads, compiles and dumps a real `ContainerBuilder` — the path on which the bundle has broken before — and the Laravel suite registers the provider against a real `Illuminate\Foundation\Application`; both bridges go from 0 % to full line coverage. CI runs them against Symfony 6.4, 7.4 and 8.x and Laravel 12 and 13, and those ranges are stated in `suggest` and in the README. The split is not cosmetic: resolved in one lock file the two frameworks deadlock, because every Laravel release that tolerates PHPUnit 11 requires `symfony/http-kernel ^7` or newer. It also keeps both frameworks out of the root `require-dev`, so a plain `composer install` stays small. A `.gitattributes` with `export-ignore` rules additionally cuts the Composer dist archive roughly in half ([#169](https://github.com/zammad/zammad-api-client-php/issues/169))
- **Fixed:** the Symfony service IDs in the custom HTTP client guide. `@Symfony\Component\HttpClient\Psr18Client` and `@Nyholm\Psr7\Factory\Psr17Factory` are class names, not registered services, so copying that YAML failed the build. FrameworkBundle aliases `Psr\Http\Client\ClientInterface`, and the `nyholm/psr7` Flex recipe registers `nyholm.psr7.psr17_factory`. The fallback for installations without Flex, in that same section, then declared the factory under its class name while the handler referenced `nyholm.psr7.psr17_factory` — reproducing the very *"has a dependency on a non-existent service"* build error it was meant to avoid. It now declares the ID the handler asks for

`GuzzleClientFactory::withToken()` / `withOAuth2()` / `withBasicAuth()` keep their signatures and now delegate to `Auth`. They remain supported shorthand for the explicit-`Auth` constructor and are not scheduled for removal; `ZammadClient::create()` is simply the recommended entry point for new code.

**One behavioural change to be aware of:** an empty or whitespace-only token (or username) is now rejected with an `InvalidArgumentException` at construction, where it previously produced a client that failed with HTTP 401 on its first request. Code that builds a client from an unset environment variable therefore fails earlier and more clearly than before. Both framework bridges resolve the client lazily, so this surfaces the first time something asks the container for it, not while the container is built: Laravel falls back to an empty token (`env('ZAMMAD_TOKEN', '')`) and throws `InvalidArgumentException`, while the Symfony bundle falls back to `%env(ZAMMAD_TOKEN)%` and throws `EnvNotFoundException` when the variable is unset, or `InvalidArgumentException` when it is set but empty. The Symfony bundle only became lazy in this release — see the fix above; before it, an empty token made `cache:clear` (and with it `composer install`) fail outright. The Symfony bundle is now covered by tests and static analysis in this repository, and an unset `ZAMMAD_TOKEN` no longer produces `EnvNotFoundException` — see the entries below ([#169](https://github.com/zammad/zammad-api-client-php/issues/169)).

## [3.1.0] — 2026-10-02

### Added
- `RoleRepository` / `RoleDTO` for `/api/v1/roles`, accessible via `$client->role()` — resolve role names to the IDs needed for `UserDTO::$role_ids`; `permission_ids`, `group_ids` and `default_at_signup` are writable on create and update ([#167](https://github.com/zammad/zammad-api-client-php/pull/167))

### Changed
- Documentation: README restructured for newcomers (install / Packagist section), cookbook setup instructions and a broken personal access token link fixed

## [3.0.0] — 2026-09-11

### Added
- PSR-18 / PSR-17 compliant HTTP layer (`RequestHandler`, `RetryAfterMiddleware`)
- Typed DTOs for all 10 resources (`Ticket`, `User`, `Organization`, `Group`, `TicketArticle`, `TicketState`, `TicketPriority`, `Tag`, `TextModule`, `Link`)
- Repository pattern with generator-based pagination (`AbstractRepository`)
- `patch()` method for partial updates via `array` or `TicketUpdateDTO`
- Proper exception hierarchy: `AuthenticationException`, `ForbiddenException`, `NotFoundException`, `BadRequestException`, `ValidationException`, `RateLimitException`, `ServerErrorException`, `NetworkException`
- `GuzzleClientFactory::withToken()` / `withOAuth2()` / `withBasicAuth()` factory methods (namespace `ZammadAPIClient\Factory`)
- PSR-3 Logger injection
- OpenAPI schema validation in integration tests
- Framework bridges for Laravel and Symfony
- `TicketArticleType` enum for article channel types
- `ClientFactoryInterface` interface + `GuzzleClientFactory` — `createHandler()` builds the `RequestHandler`
- `ImpersonationHandler` — stateless decorator for API impersonation, scoped via `new ZammadClient(new ImpersonationHandler($handler, $userId))`

### Changed
- **Breaking:** PHP >= 8.1 required
- **Breaking:** `ZammadClient::withToken()` / `withOAuth2()` / `withBasicAuth()` → `new ZammadClient(GuzzleClientFactory::with*())` (namespace `ZammadAPIClient\Factory`).
  Non-Guzzle via `new ZammadClient(new RequestHandler($psr18Client, $psr17Factory, $url))`.
- **Breaking:** `ZammadClient::withClient()` removed; the `ZammadClient` constructor accepts `RequestHandlerInterface|ClientFactoryInterface`
- **Breaking:** `ZammadClient::setOnBehalfOfUser()` / `unsetOnBehalfOfUser()` / `onBehalfOf()` / `performOnBehalfOf()` removed.
  Impersonation via `new ZammadClient(new ImpersonationHandler($handler, $userId))`.
- **Breaking:** `RequestHandlerInterface::setOnBehalfOfUser()` / `getOnBehalfOfUser()` removed.
  `RequestHandler` no longer holds any impersonation state.
- **Breaking:** `RequestHandlerInterface::getRaw()` signature extended by a `$headers` parameter
- **Breaking:** `Client` class replaced by `ZammadClient` with repository accessors
- **Breaking:** Array return values replaced by typed DTOs
- **Breaking:** Guzzle used as default transport; any PSR-18 client can be injected via `RequestHandler`
- `getListKey()` in repository classes is no longer abstract; it defaults to `$this->resourcePath`
- Typed repository accessors (`$client->ticket()`, `$client->user()`, …) are the recommended public API; the underlying `repo()` method is `@internal`.

### Removed
- `ZammadClient::aliasMap()`, `resolveAlias()`, `__call()` — deprecated in v3.0, now removed
- Shared mutable impersonation state in `RequestHandler`

---

## Previous v2 releases

### [2.3.0] - 2026-06-10
- Added [#78](https://github.com/zammad/zammad-api-client-php/pull/78) - Ticket linking support (Link resource).
- Added [#59](https://github.com/zammad/zammad-api-client-php/issues/59) - Admin-scoped tag support.
- Added [#126](https://github.com/zammad/zammad-api-client-php/pull/126) - Configurable test timeout via `ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TIMEOUT` and `connection_options` support in HTTPClient.
- Fixed [#52](https://github.com/zammad/zammad-api-client-php/issues/52) - `getAttachmentContent()` to return string instead of Stream object.
- Added [#43](https://github.com/zammad/zammad-api-client-php/issues/43) - `sort_by` and `order_by` parameters for `search()`.
- Fixed [#77](https://github.com/zammad/zammad-api-client-php/issues/77) - `getID()` to handle null values and always return a string.

### [2.2.3] - 2026-06-08
- Fixed [#64](https://github.com/zammad/zammad-api-client-php/issues/64) - Use `From` header instead of deprecated `X-On-Behalf-Of`.

### [2.2.2] - 2026-04-27
- Fix PHP deprecation warnings.
