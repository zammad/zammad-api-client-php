# Zammad API PHP Client — Cookbook

## Setup

```bash
cp .env.example .env
# Edit .env with your Zammad URL + token
```

## Recipes

| File | Description |
|---|---|
| `00-plain.php` | Guzzle setup (used by recipes 01-06). Standalone, copy-paste-ready. |
| `00-laravel.php` | Laravel service container setup. |
| `00-symfony.php` | Symfony bundle setup. |
| `00-slim.php` | Non-Guzzle setup (Symfony HttpClient + Nyholm PSR-17). |
| `01-quick-start.php` | Client instantiation + find ticket #1. |
| `02-crud.php` | Create, read, and delete tickets. |
| `03-listing.php` | `all()` streaming, `list()` pagination, `totalCount()`. |
| `04-updates.php` | `patch()` partial update + `TicketUpdateDTO`. |
| `05-impersonation.php` | `ImpersonationHandler` for scoped on-behalf-of requests. |
| `06-search.php` | Full-text search via `search()` and `searchList()`. |

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
