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