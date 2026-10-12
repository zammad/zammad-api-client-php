<?php

/**
 * Zammad API Client — Symfony bundle setup.
 *
 * Requirements:
 *   composer require zammad/zammad-api-client-php
 *   config/bundles.php → ZammadAPIClient\Bridge\SymfonyBundle::class => ['all' => true]
 *   config/packages/zammad.yaml:
 *     zammad:
 *       url: '%env(ZAMMAD_URL)%'
 *       token: '%env(ZAMMAD_TOKEN)%'
 *
 * Usage in service:
 *
 *   use ZammadAPIClient\ZammadClient;
 *
 *   class TicketService
 *   {
 *       public function __construct(private ZammadClient $zammad) {}
 *
 *       public function findTicket(int $id)
 *       {
 *           return $this->zammad->ticket()->find($id);
 *       }
 *   }
 *
 * Or resolve manually:
 *   $client = $container->get(ZammadClient::class);
 */

declare(strict_types=1);

// Already registered by the bundle — this file is for reference.
fwrite(STDOUT, "See docblock above for Symfony setup instructions.\n");
