## [2.3.0] - 2026-06-10
- Added [#78](https://github.com/zammad/zammad-api-client-php/pull/78) - Ticket linking support (Link resource).
- Added [#59](https://github.com/zammad/zammad-api-client-php/issues/59) - Admin-scoped tag support.
- Added [#126](https://github.com/zammad/zammad-api-client-php/pull/126) - Configurable test timeout via `ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TIMEOUT` and `connection_options` support in HTTPClient.
- Fixed [#52](https://github.com/zammad/zammad-api-client-php/issues/52) - `getAttachmentContent()` to return string instead of Stream object.
- Added [#43](https://github.com/zammad/zammad-api-client-php/issues/43) - `sort_by` and `order_by` parameters for `search()`.
- Fixed [#77](https://github.com/zammad/zammad-api-client-php/issues/77) - `getID()` to handle null values and always return a string.

## [2.2.3] - 2026-06-08
- Fixed [#64](https://github.com/zammad/zammad-api-client-php/issues/64) - Use `From` header instead of deprecated `X-On-Behalf-Of`.

## [2.2.2] - 2026-04-27
- Fix PHP deprecation warnings.

## [2.2.1] - 2025-09-10
- Improve handling of lowercase HTTP response headers.

## [2.2.0] - 2023-05-11
- Switch to dual licensing under AGPL-3.0 or MIT licenses.

## [2.1.0] - 2022-12-01
- Added [#48](https://github.com/zammad/zammad-api-client-php/pull/48) - Allowing injection of custom client.

## [2.0.5] - 2022-09-02
- Fixed [#42](https://github.com/zammad/zammad-api-client-php/issues/42) - Call to undefined method GuzzleHttp\Exception\ConnectException::getResponse().

## [2.0.4] - 2022-07-29
- Fixed [#49](https://github.com/zammad/zammad-api-client-php/issues/49) - "on_behalf_of_user" never gets initialized, which leads to conflicts.
- Fixed [#47](https://github.com/zammad/zammad-api-client-php/issues/47) - Make Guzzles HTTP connect_timeout configurable or set a decent default.
- Fixed [#46](https://github.com/zammad/zammad-api-client-php/issues/46) - Wrong documentation for SSL verification switch.

## [2.0.3] - 2022-04-14
- Updated tests to be compatible with new Zammad versions.