# Changelog

## [3.0.0] — unreleased

### Added
- PSR-18 / PSR-17 compliant HTTP layer (`RequestHandler`, `RetryAfterMiddleware`)
- Typed DTOs for all 10 resources (`Ticket`, `User`, `Organization`, `Group`, `TicketArticle`, `TicketState`, `TicketPriority`, `Tag`, `TextModule`, `Link`)
- Repository pattern with generator-based pagination (`AbstractRepository`)
- `patch()` method for partial updates via `array` or `TicketUpdateDTO`
- Proper exception hierarchy: `AuthenticationException`, `NotFoundException`, `ValidationException`, `RateLimitException`, `ServerErrorException`, `NetworkException`
- `ZammadClient::withToken()` / `withOAuth2()` / `withBasicAuth()` factory methods
- PSR-3 Logger injection
- OpenAPI schema validation in integration tests
- Framework bridges for Laravel and Symfony
- `TicketArticleType` enum for article channel types
- `ClientFactoryInterface` interface + `GuzzleClientFactory` — `createHandler()` baut den `RequestHandler`
- `ImpersonationHandler` — stateless decorator fur API-Impersonation
- `ImpersonationHandler` — scoped via `new ZammadClient(new ImpersonationHandler($handler, $userId))`

### Changed
- **Breaking:** PHP >= 8.1 required
- **Breaking:** `ZammadClient::withToken()` / `withOAuth2()` / `withBasicAuth()` → `new ZammadClient(GuzzleClientFactory::with*())` (Namespace `ZammadAPIClient\Factory`).
  Non-Guzzle via `new ZammadClient(new RequestHandler($psr18Client, $psr17Factory, $url))`.
- **Breaking:** `ZammadClient::withClient()` entfernt; `ZammadClient` constructor akzeptiert `RequestHandlerInterface|ClientFactoryInterface`
- **Breaking:** `ZammadClient::setOnBehalfOfUser()` / `unsetOnBehalfOfUser()` / `onBehalfOf()` / `performOnBehalfOf()` entfernt.
  Impersonation via `new ZammadClient(new ImpersonationHandler($handler, $userId))`.
- **Breaking:** `RequestHandlerInterface::setOnBehalfOfUser()` / `getOnBehalfOfUser()` entfernt.
  `RequestHandler` halt keinen Impersonation-State mehr.
- **Breaking:** `RequestHandlerInterface::getRaw()` Signatur um `$headers`-Parameter erweitert
- **Breaking:** `Client` class replaced by `ZammadClient` with repository accessors
- **Breaking:** Array return values replaced by typed DTOs
- **Breaking:** Guzzle used as default transport; `withClient()` supports any PSR-18 client
- `getListKey()` in Repository-Klassen nicht mehr abstrakt; Default ist `$this->resourcePath`

### Removed
- Magic resource accessor (`$client->ticket()`) — ersatzlos entfernt. Nutze `$client->repo(TicketRepository::class)`.
- `ZammadClient::aliasMap()`, `resolveAlias()`, `__call()` — deprecated in v3.0, jetzt entfernt
- Shared mutable Impersonation-State aus `RequestHandler` entfernt

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
