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

### Changed
- **Breaking:** PHP >= 8.1 required
- **Breaking:** `Client` class replaced by `ZammadClient` with repository accessors
- **Breaking:** Array return values replaced by typed DTOs
- **Breaking:** Guzzle used as default transport; `withClient()` supports any PSR-18 client

### Deprecated
- Magic resource accessor (`$client->ticket()`) — triggers `E_USER_DEPRECATED`. Use `$client->repo(TicketRepository::class)` instead. Removed in v4.0.

### Removed
- `ResourceType` constants — use typed repository methods
- `AbstractResource` 640-line monolith — replaced by individual repositories

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
