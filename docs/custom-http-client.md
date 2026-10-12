# Using a Custom HTTP Client

The `ZammadClient` constructor accepts any PSR-18 `ClientInterface` implementation via `RequestHandlerInterface`. `ZammadClient::create()` uses Guzzle for convenience, but nothing is lost by building the handler yourself: credentials, retry behaviour and logging are configured the same way either side.

Rate-limit retry (HTTP 429) is handled automatically by `RequestHandler` — no manual wiring needed.

A runnable version of the setup below is in [`examples/cookbook/07-custom-http-client.php`](../examples/cookbook/07-custom-http-client.php).

## Symfony HttpClient

Requires `composer require symfony/http-client nyholm/psr7` (or `guzzlehttp/psr7` for PSR-17 factories).

```php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use GuzzleHttp\Psr7\HttpFactory;
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
    new HttpFactory(),
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

If you're migrating away from Guzzle, start with `ZammadClient::create()` and
refactor step by step — the handler built above supports exactly the same options.

## Laravel

The `LaravelServiceProvider` binds `ZammadClient` as a singleton. To use Symfony instead, override the binding in your `AppServiceProvider`:

```php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use GuzzleHttp\Psr7\HttpFactory;
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
                new HttpFactory(),
                config('zammad.url'),
                maxRetries: 3,
            );
            return new ZammadClient($handler);
        });
    }
}
```

## Symfony

The `SymfonyBundle` can be extended or replaced entirely by defining your own service definition:

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

    # Or with a custom PSR-18 stack:
    app.zammad_client:
        class: ZammadAPIClient\ZammadClient
        arguments:
            - '@app.zammad_request_handler'
```

## Custom PSR-18 Middleware Stack

`RequestHandler` internally applies rate-limit retry via `RetryAfterMiddleware`. You only need to compose your own middleware around the base client — retry behavior is added automatically (set `maxRetries: 0` to disable).

```php
$http = new LoggingMiddleware(
    new MetricsMiddleware(
        new Psr18Client(),
    ),
);

// Rate-limit retry is applied internally by RequestHandler
$handler = new RequestHandler($http, new HttpFactory(), 'https://zammad.example', maxRetries: 5);
```

## Important Security Notes

- **Configure timeouts**: Always set `timeout` and connection timeout on your PSR-18 client. The factory methods default to 30s/10s respectively.
- **Verify TLS**: Do not disable TLS verification in production. The factory methods default to `verifySsl = true`.
- **Redirects**: The factory methods disable redirect following (`allow_redirects = false`). If using your own PSR-18 client, configure redirect behavior carefully — following redirects may leak the `Authorization` header to unintended hosts.
- **Debug logging**: Use a PSR-3 logger via `ConnectionConfig`. Never enable raw HTTP debug output in production — it will expose API tokens in log files.
