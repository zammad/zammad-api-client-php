<?php

declare(strict_types=1);

namespace ZammadAPIClient\Bridge;

use Illuminate\Support\ServiceProvider;
use ZammadAPIClient\Factory\GuzzleClientFactory;
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
 *   ZAMMAD_URL=https://zammad.example.com/api/v1
 *   ZAMMAD_TOKEN=your-api-token
 *   ```
 *
 *   Or edit `config/zammad.php` directly.
 *
 * **Usage**
 *
 * ```php
 * use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
 * use ZammadAPIClient\ZammadClient;
 *
 * class TicketController
 * {
 *     public function __construct(private ZammadClient $zammad) {}
 *
 *     public function show(int $id)
 *     {
 *         $ticket = $this->zammad->repo(TicketRepository::class)->find($id);
 *     }
 * }
 *
 * // Or resolve manually from the container:
 * $tickets = app(ZammadClient::class)->repo(TicketRepository::class)->all();
 * ```
 *
 * **Configuration precedence**
 *
 * 1. `config/zammad.php` values (after `vendor:publish`)
 * 2. `ZAMMAD_URL` / `ZAMMAD_TOKEN` environment variables (`.env`)
 * 3. Built-in defaults (`http://127.0.0.1:8098/api/v1`, empty token)
 *
 * @see ZammadClient::withToken()
 */
final class LaravelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/zammad.php' => config_path('zammad.php'),
        ], 'zammad-config');
    }

    public function register(): void
    {
        $this->app->singleton(ZammadClient::class, function (): ZammadClient {
            $url = config('zammad.url') ?: env('ZAMMAD_URL', 'http://127.0.0.1:8098');
            $token = config('zammad.token') ?: env('ZAMMAD_TOKEN', '');

            return new ZammadClient(
                GuzzleClientFactory::withToken($url, $token),
            );
        });
    }
}
