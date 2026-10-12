# Changelog

## [3.2.0] — unreleased

### Added
- `Auth` — credentials as a transport-independent value: `Auth::token()`, `Auth::oauth2()`, `Auth::basic()`. The constructor is private, so the set of valid schemes is closed by construction; empty tokens and usernames are rejected on creation instead of surfacing as a 401 later
- `ZammadClient::create(string $url, Auth $auth, ?ConnectionConfig $config = null)` — one-line entry point for the default (Guzzle) transport
- `RequestHandler` accepts an optional `auth:` argument and applies the `Authorization` header per request

### Deprecated
- `GuzzleClientFactory::withToken()` / `withOAuth2()` / `withBasicAuth()` — use the constructor with an `Auth` value instead: `new GuzzleClientFactory($url, Auth::token($token))`. They keep working and will be removed in 4.0

### Changed
- Authentication no longer depends on the HTTP client supporting client-level default headers. It is applied by `RequestHandler` when composing each request, so any PSR-18 transport authenticates the same way — the hand-written `AuthMiddleware` that the docs previously required for Symfony's `Psr18Client` is obsolete
- `GuzzleClientFactory` takes `(string $url, Auth $auth, ?ConnectionConfig $config)` and no longer sets an `Authorization` default header on the Guzzle client. Its constructor was private before, so no caller is affected
- Documentation: corrected examples that referenced `ZammadClient::withToken()` and the impersonation methods removed in 3.0.0 ([#168](https://github.com/zammad/zammad-api-client-php/issues/168))
- **Fixed:** the Laravel override and custom-middleware examples in the custom HTTP client guide built a client with no credentials at all, so every request would have failed with HTTP 401. They now pass an `Auth` to the `RequestHandler`

`GuzzleClientFactory::withToken()` / `withOAuth2()` / `withBasicAuth()` keep their signatures and now delegate to `Auth`; they are deprecated as of this release but remain functional until 4.0.

**One behavioural change to be aware of:** an empty or whitespace-only token (or username) is now rejected with an `InvalidArgumentException` at construction, where it previously produced a client that failed with HTTP 401 on its first request. Code that builds a client from an unset environment variable will fail earlier and more clearly than before — including the Laravel and Symfony bridges, which fall back to an empty token when `ZAMMAD_TOKEN` is not set.

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
