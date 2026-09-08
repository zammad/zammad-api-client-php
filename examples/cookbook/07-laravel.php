<?php

/**
 * Zammad API Client — Laravel service container setup.
 *
 * Requirements:
 *   composer require zammad/zammad-api-client-php
 *   config/app.php → add ZammadAPIClient\Bridge\LaravelServiceProvider::class
 *   .env → ZAMMAD_URL=https://zammad.example ZAMMAD_TOKEN=xxx
 *
 * Usage in controller/command:
 *
 *   use ZammadAPIClient\ZammadClient;
 *
 *   class TicketController
 *   {
 *       public function __construct(private ZammadClient $zammad) {}
 *
 *       public function show(int $id)
 *       {
 *           $ticket = $this->zammad->ticket()->find($id);
 *       }
 *   }
 *
 * Or resolve manually:
 *   $client = app(ZammadClient::class);
 */

declare(strict_types=1);

// Already registered by the service provider — this file is for reference.
fwrite(STDOUT, "See docblock above for Laravel setup instructions.\n");
