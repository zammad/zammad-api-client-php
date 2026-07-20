<?php

declare(strict_types=1);

namespace ZammadAPIClient\Bridge;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use ZammadAPIClient\Factory\GuzzleClientFactory;
use ZammadAPIClient\ZammadClient;

/**
 * Registers {@see ZammadClient} as an autowired service in the Symfony DI container.
 *
 * **Why use this?**
 *
 * Without this bundle you'd configure the client manually in `services.yaml`
 * and wire environment variables yourself. This bundle does that once, registers
 * {@see ZammadClient} for autowiring, and exposes an `zammad_client` alias — so
 * any service can type-hint `ZammadClient` and get a ready-to-use instance.
 *
 * **Setup**
 *
 * - Register the bundle in `config/bundles.php`:
 *
 *   ```php
 *   return [
 *       // ...
 *       ZammadAPIClient\Bridge\SymfonyBundle::class => ['all' => true],
 *   ];
 *   ```
 *
 * - Configure the connection in `config/packages/zammad.yaml`:
 *
 *   ```yaml
 *   zammad:
 *     url: '%env(ZAMMAD_URL)%'
 *     token: '%env(ZAMMAD_TOKEN)%'
 *   ```
 *
 * - Set the environment variables (`.env` or `.env.local`):
 *
 *   ```env
 *   ZAMMAD_URL=https://zammad.example.com/api/v1
 *   ZAMMAD_TOKEN=your-api-token
 *   ```
 *
 * **Usage**
 *
 * ```php
 * use ZammadAPIClient\Endpoints\Tickets\TicketRepository;
 * use ZammadAPIClient\ZammadClient;
 *
 * class TicketService
 * {
 *     public function __construct(private ZammadClient $zammad) {}
 *
 *     public function findTicket(int $id)
 *     {
 *         return $this->zammad->repo(TicketRepository::class)->find($id);
 *     }
 * }
 *
 * // Or resolve manually from the container:
 * // $container->get(ZammadClient::class)->repo(TicketRepository::class)->all();
 * // $container->get('zammad_client')->repo(TicketRepository::class)->all();
 * ```
 *
 * @see ZammadClient::withToken()
 */
final class SymfonyBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new class implements ExtensionInterface {
            public function load(array $configs, ContainerBuilder $container): void
            {
                $resolved = [];
                foreach ($configs as $config) {
                    $resolved = array_merge($resolved, $config);
                }

                $url   = $resolved['url']   ?? (string) ($_ENV['ZAMMAD_URL']   ?? 'http://127.0.0.1:8098/api/v1');
                $token = $resolved['token'] ?? (string) ($_ENV['ZAMMAD_TOKEN'] ?? '');

                $client = new ZammadClient(
                    GuzzleClientFactory::withToken($url, $token),
                );
                $container->set(ZammadClient::class, $client);
                $container->setAlias('zammad_client', ZammadClient::class);
                $container->registerForAutoconfiguration(ZammadClient::class)->setAutowired(true);
            }

            public function getNamespace(): string
            {
                return '';
            }

            public function getXsdValidationBasePath(): string
            {
                return '';
            }

            public function getAlias(): string
            {
                return 'zammad';
            }
        };
    }
}
