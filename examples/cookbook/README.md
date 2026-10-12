# Zammad API PHP Client — Cookbook

## Setup

The recipes read their configuration from environment variables (via `getenv()`).
Export your Zammad URL and token before running them:

```bash
export ZAMMAD_URL=https://zammad.example
export ZAMMAD_TOKEN=your-token
```

## Recipes

**Getting started**

| File | Description |
|---|---|
| [`00-connect.php`](00-connect.php) | Connect to Zammad. Standalone, copy-paste-ready; recipes 01-06 repeat this block. |
| [`01-quick-start.php`](01-quick-start.php) | Client instantiation + find ticket #1. |
| [`02-crud.php`](02-crud.php) | Create, read, and delete tickets. |
| [`03-listing.php`](03-listing.php) | `all()` streaming, `list()` pagination, `totalCount()`. |
| [`04-updates.php`](04-updates.php) | `patch()` partial update + `TicketUpdateDTO`. |
| [`05-impersonation.php`](05-impersonation.php) | `ImpersonationHandler` for scoped on-behalf-of requests. |
| [`06-search.php`](06-search.php) | Full-text search via `search()` and `searchList()`. |

**Custom HTTP client**

| File | Description |
|---|---|
| [`07-custom-http-client.php`](07-custom-http-client.php) | Use your own PSR-18 client instead of Guzzle (example: Symfony HttpClient + Nyholm PSR-17). See [docs/custom-http-client.md](../../docs/custom-http-client.md). |

**Framework integration**

| File | Description |
|---|---|
| [`08-laravel.php`](08-laravel.php) | Laravel service container setup. |
| [`09-symfony.php`](09-symfony.php) | Symfony bundle setup. |
| [`10-slim.php`](10-slim.php) | Slim 4 / PHP-DI setup. No bridge needed — the pattern for any PSR-11 container. |

## Run

```bash
# Single recipe:
php examples/cookbook/01-quick-start.php

# All recipes:
for f in examples/cookbook/0[1-6]*.php; do echo "=== $f ===" && php "$f"; done
```

## Environment

All recipes accept these environment variables:

| Variable | Default |
|---|---|
| `ZAMMAD_URL` | `http://localhost:3000` |
| `ZAMMAD_TOKEN` | *(required)* |
| `ZAMMAD_USERNAME` | — (for basic auth fallback) |
| `ZAMMAD_PASSWORD` | — |
