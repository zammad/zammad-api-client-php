<?php

declare(strict_types=1);

namespace ZammadAPIClient\Bridge;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use ZammadAPIClient\Core\Auth;
use ZammadAPIClient\ZammadClient;

/**
 * Registers {@see ZammadClient} as a singleton in the Laravel service container.
 *
 * **Why use this?**
 *
 * Instead of creating a {@see ZammadClient} manually in every controller or
 * command, this provider wires it once as a shared service. You inject it via
 * the container and get the same instance everywhere — consistent config,
 * consistent middleware stack, one place to change credentials.
 *
 * **Setup**
 *
 * - Add the provider to `config/app.php`:
 *
 *   ```php
 *   'providers' => [
 *       // ...
 *       ZammadAPIClient\Bridge\LaravelServiceProvider::class,
 *   ],
 *   ```
 *
 * - Publish the configuration file:
 *
 *   ```bash
 *   php artisan vendor:publish --tag=zammad-config
 *   ```
 *
 *   This copies the default config to `config/zammad.php`.
 *
 * - Set your credentials in `.env`:
 *
 *   ```env
 *   ZAMMAD_URL=https://zammad.example.com
 *   ZAMMAD_TOKEN=your-api-token
 *   ```
 *
 *   Or edit `config/zammad.php` directly.
 *
 * **Usage**
 *
 * ```php
 * use ZammadAPIClient\ZammadClient;
 *
 * class TicketController
 * {
 *     public function __construct(private ZammadClient $zammad) {}
 *
 *     public function show(int $id)
 *     {
 *         $ticket = $this->zammad->ticket()->find($id);
 *     }
 * }
 *
 * // Or resolve manually from the container:
 * $tickets = app(ZammadClient::class)->ticket()->all();
 * ```
 *
 * **Configuration precedence**
 *
 * 1. `config/zammad.php` values (after `vendor:publish`)
 * 2. `ZAMMAD_URL` / `ZAMMAD_TOKEN` environment variables (`.env`)
 * 3. Built-in default URL (`http://127.0.0.1:8098`)
 *
 * The environment is read by the packaged config file, which `register()` merges
 * in — never by this provider at resolve time. That distinction matters: under
 * `php artisan config:cache` Laravel skips the `LoadEnvironmentVariables`
 * bootstrapper entirely, so `.env` is never loaded and an `env()` call made while
 * resolving a service returns its default rather than the configured value.
 *
 * A token is required: there is no usable default for it. If neither
 * `config('zammad.token')` nor `ZAMMAD_TOKEN` is set, resolving
 * {@see ZammadClient} from the container throws `InvalidArgumentException`.
 *
 * @see ZammadClient::create()
 */
final class LaravelServiceProvider extends ServiceProvider
{
    /**
     * Last-resort fallback, mirroring the default in the packaged config file.
     */
    private const DEFAULT_URL = 'http://127.0.0.1:8098';

    private const PACKAGED_CONFIG = __DIR__ . '/../../config/zammad.php';

    public function boot(): void
    {
        $this->publishes([
            self::PACKAGED_CONFIG => config_path('zammad.php'),
        ], 'zammad-config');
    }

    public function register(): void
    {
        // Merging the packaged defaults is what makes `ZAMMAD_URL` / `ZAMMAD_TOKEN`
        // work without `vendor:publish`, and it is the only correct place to read
        // them. See the note on env() below.
        $this->mergeConfigFrom(self::PACKAGED_CONFIG, 'zammad');

        $this->app->singleton(ZammadClient::class, static function (Application $app): ZammadClient {
            /** @var Repository $config */
            $config = $app->make('config');

            return ZammadClient::create(
                self::setting($config->get('zammad.url'), self::DEFAULT_URL),
                Auth::token(self::setting($config->get('zammad.token'), '')),
            );
        });
    }

    /**
     * Narrows one configuration value to the string the client expects.
     *
     * `Repository::get()` returns `mixed`, and `config/zammad.php` is a plain PHP
     * file the application owns and is free to edit. Without this narrowing, an
     * array or a `null` left in there under `declare(strict_types=1)` surfaces as
     * a `TypeError` thrown from inside {@see Auth::token()} or
     * {@see ZammadClient::create()} — naming the client rather than the config
     * file the reader actually has to go and fix.
     *
     * An empty string counts as unset, so the built-in default still applies to
     * a published config file whose own `env()` call resolved to `''`.
     */
    private static function setting(mixed $value, string $default): string
    {
        return is_string($value) && $value !== '' ? $value : $default;
    }
}
