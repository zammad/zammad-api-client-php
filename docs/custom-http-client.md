# Using a Custom HTTP Client

The `ZammadClient` constructor accepts any PSR-18 `ClientInterface` implementation via `RequestHandlerInterface`. `ZammadClient::create()` uses Guzzle for convenience, but nothing is lost by building the handler yourself: credentials, retry behaviour and logging are configured the same way either side.

Rate-limit retry (HTTP 429) is handled automatically by `RequestHandler` — no manual wiring needed.

A runnable version of the setup below is in [`examples/cookbook/07-custom-http-client.php`](../examples/cookbook/07-custom-http-client.php).

## Symfony HttpClient

Requires `composer require symfony/http-client nyholm/psr7`. Any PSR-17 factory works; `guzzlehttp/psr7` is already installed as a dependency if you prefer it.

```php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use ZammadAPIClient\Core\Auth;
use ZammadAPIClient\Core\Transport\RequestHandler;
use ZammadAPIClient\ZammadClient;

$handler = new RequestHandler(
    new Psr18Client(HttpClient::create([
        'timeout'         => 30,
        'max_duration'    => 30,
        'verify_peer'     => true,
        'verify_host'     => true,
    ])),
    new Psr17Factory(),
    'https://zammad.example',
    maxRetries: 3,
    auth: Auth::token('your-zammad-api-token'),
);

$client = new ZammadClient($handler);
```

### Authentication

Pass an `Auth` to the `RequestHandler`, as above. It applies the `Authorization`
header to each outgoing request, so it does not rely on the PSR-18 client
supporting client-level default headers — PSR-18 has no such concept, and
Symfony's `Psr18Client` does not offer one.

`Auth::token()`, `Auth::oauth2()` and `Auth::basic()` are the available schemes.
If you need credentials that change at runtime (an OAuth2 token being refreshed,
say), wrap your PSR-18 client in a decorator that sets the header per request and
leave `auth` unset.

Constructing a `RequestHandler` with an `Auth` and a plain `http://` base URL
throws an `InvalidArgumentException`, because the credential would travel the
network in cleartext. Loopback hosts are exempt; for anything else that is
already protected by other means — a VPN, a service mesh, an SSH tunnel — pass
`allowInsecureHttp: true`. Note that the check sees only the `Auth` you hand it:
a decorator that sets the header itself, as described above, is invisible to it
and remains your responsibility.

If you're migrating away from Guzzle, start with `ZammadClient::create()` and
refactor step by step — the handler built above supports exactly the same options.

## Laravel

The `LaravelServiceProvider` binds `ZammadClient` as a singleton. To use Symfony instead, override the binding in your `AppServiceProvider`:

```php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use ZammadAPIClient\Core\Auth;
use ZammadAPIClient\Core\Transport\RequestHandler;
use ZammadAPIClient\ZammadClient;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ZammadClient::class, function () {
            $handler = new RequestHandler(
                new Psr18Client(HttpClient::create([
                    'timeout' => config('zammad.timeout', 30),
                ])),
                new Psr17Factory(),
                config('zammad.url'),
                maxRetries: 3,
                auth: Auth::token(config('zammad.token')),
            );
            return new ZammadClient($handler);
        });
    }
}
```

## Symfony

The `SymfonyBundle` can be extended or replaced entirely by defining your own service definition.

Both variants below register the client under the service ID `ZammadAPIClient\ZammadClient`,
because that is the ID Symfony resolves when a service type-hints `ZammadClient`. Pick
**one** of them, and remove `SymfonyBundle` from `config/bundles.php` — the bundle
registers the very same ID, so leaving it enabled means one definition silently wins over
the other.

Either the default (Guzzle) transport:

```yaml
# config/services.yaml
services:
    app.zammad_auth:
        class: ZammadAPIClient\Core\Auth
        factory: ['ZammadAPIClient\Core\Auth', 'token']
        arguments:
            $token: '%env(ZAMMAD_TOKEN)%'

    ZammadAPIClient\ZammadClient:
        factory: ['ZammadAPIClient\ZammadClient', 'create']
        arguments:
            $url: '%env(ZAMMAD_URL)%'
            $auth: '@app.zammad_auth'
```

…or your own PSR-18 stack. Note that the class service ID is the one that gets the custom
handler — a separate `app.zammad_client` ID would be ignored by autowiring, and every
service type-hinting `ZammadClient` would silently keep using the Guzzle definition above:

```yaml
# config/services.yaml
services:
    app.zammad_auth:
        class: ZammadAPIClient\Core\Auth
        factory: ['ZammadAPIClient\Core\Auth', 'token']
        arguments:
            $token: '%env(ZAMMAD_TOKEN)%'

    app.zammad_request_handler:
        class: ZammadAPIClient\Core\Transport\RequestHandler
        arguments:
            $httpClient: '@Psr\Http\Client\ClientInterface'
            $factory: '@nyholm.psr7.psr17_factory'
            $baseUrl: '%env(ZAMMAD_URL)%'
            $auth: '@app.zammad_auth'

    ZammadAPIClient\ZammadClient:
        arguments:
            - '@app.zammad_request_handler'
```

The two service IDs in the custom stack are the ones Symfony actually registers:
FrameworkBundle aliases `Psr\Http\Client\ClientInterface` to its `psr18.http_client`
service as soon as `symfony/http-client` is installed, and `nyholm.psr7.psr17_factory`
comes from the `nyholm/psr7` Flex recipe. The class names are *not* service IDs — the
package itself ships no DI configuration. If you installed it without Flex, declare the
factory yourself:

```yaml
    Nyholm\Psr7\Factory\Psr17Factory: ~
```

## Custom PSR-18 Middleware Stack

`RequestHandler` internally applies rate-limit retry via `RetryAfterMiddleware`. You only need to compose your own middleware around the base client — retry behavior is added automatically (set `maxRetries: 0` to disable).

`LoggingMiddleware` and `MetricsMiddleware` below stand in for your own decorators.

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;
use ZammadAPIClient\Core\Auth;
use ZammadAPIClient\Core\Transport\RequestHandler;

$http = new LoggingMiddleware(
    new MetricsMiddleware(
        new Psr18Client(),
    ),
);

// Rate-limit retry is applied internally by RequestHandler
$handler = new RequestHandler(
    $http,
    new Psr17Factory(),
    'https://zammad.example',
    maxRetries: 5,
    auth: Auth::token('your-zammad-api-token'),
);
```

## Important Security Notes

Building the handler yourself means the defaults `ZammadClient::create()` applies are no longer applied for you. Each of these is now your responsibility:

- **Configure timeouts**: Always set a request and a connection timeout on your PSR-18 client. `ZammadClient::create()` defaults to 30s and 10s (`ConnectionConfig::$timeout` / `$connectTimeout`).
- **Verify TLS**: Do not disable TLS verification in production. `ZammadClient::create()` defaults to `verifySsl = true`.
- **Redirects**: `ZammadClient::create()` disables redirect following (`allow_redirects = false`). Configure your own client carefully — following redirects may leak the `Authorization` header to unintended hosts.
- **Plain HTTP**: `RequestHandler` refuses an `Auth` over `http://` to a non-loopback host, and `allowInsecureHttp: true` turns that off. The check cannot see an `Authorization` header your own PSR-18 decorator adds — if you authenticate that way, make sure the URL is `https://` yourself.
- **Debug logging**: Pass a PSR-3 logger as the `logger:` argument of `RequestHandler` (on the `create()` path it comes from `ConnectionConfig::$logger`). Never enable raw HTTP debug output in production — it will expose API tokens in log files.
