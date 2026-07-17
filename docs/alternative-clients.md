# Using an Alternative PSR-18 HTTP Client

The `ZammadClient` constructor accepts any PSR-18 `ClientInterface` implementation via `RequestHandlerInterface`. The factory methods (`withToken()`, `withOAuth2()`, `withBasicAuth()`) use Guzzle by default for convenience, but you can inject any PSR-18 client directly.

Rate-limit retry (HTTP 429) is handled automatically by `RequestHandler` — no manual wiring needed.

## Symfony HttpClient

Requires `composer require symfony/http-client nyholm/psr7` (or `guzzlehttp/psr7` for PSR-17 factories).

```php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use GuzzleHttp\Psr7\HttpFactory;
use ZammadAPIClient\Core\RequestHandler;
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
);

$client = new ZammadClient($handler);
```

### With Authentication Headers

Symfony's `Psr18Client` does not accept global default headers. Instead, configure them on the `RequestHandler` by passing them via `$options` on each `request()` call, or use a PSR-18 middleware to inject the `Authorization` header.

**Option A — Inject headers via a PSR-18 decorator middleware:**

```php
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

final class AuthMiddleware implements ClientInterface
{
    public function __construct(
        private ClientInterface $next,
        private string $token,
    ) {}

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->next->sendRequest(
            $request->withHeader('Authorization', "Token token={$this->token}")
        );
    }
}

$http = new AuthMiddleware(
    new Psr18Client(HttpClient::create(['timeout' => 30])),
    'your-zammad-api-token',
);

// Rate-limit retry is handled automatically by RequestHandler
$handler = new RequestHandler($http, new HttpFactory(), 'https://zammad.example');
```

**Option B — Build the Guzzle client with auth headers, then swap to Symfony later:**

If you're migrating away from Guzzle, start with `ZammadClient::withToken()` and refactor step by step.

## Laravel

The `LaravelServiceProvider` binds `ZammadClient` as a singleton. To use Symfony instead, override the binding in your `AppServiceProvider`:

```php
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use GuzzleHttp\Psr7\HttpFactory;
use ZammadAPIClient\Core\RequestHandler;
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
    ZammadAPIClient\ZammadClient:
        factory: ['ZammadAPIClient\ZammadClient', 'withToken']
        arguments:
            $url: '%env(ZAMMAD_URL)%'
            $token: '%env(ZAMMAD_TOKEN)%'

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
