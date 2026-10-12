<?php

declare(strict_types=1);

namespace ZammadAPIClient\Bridge;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use ZammadAPIClient\Core\Auth;
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
 *   ZAMMAD_URL=https://zammad.example.com
 *   ZAMMAD_TOKEN=your-api-token
 *   ```
 *
 * **Usage**
 *
 * ```php
 * use ZammadAPIClient\ZammadClient;
 *
 * class TicketService
 * {
 *     public function __construct(private ZammadClient $zammad) {}
 *
 *     public function findTicket(int $id)
 *     {
 *         return $this->zammad->ticket()->find($id);
 *     }
 * }
 *
 * // Or resolve manually from the container:
 * // $container->get(ZammadClient::class)->ticket()->all();
 * // $container->get('zammad_client')->ticket()->all();
 * ```
 *
 * @see ZammadClient::create()
 */
final class SymfonyBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new class implements ExtensionInterface {
            /**
             * Registers the configured Zammad client with the container.
             *
             * @param array<int, array<string, mixed>> $configs
             */
            public function load(array $configs, ContainerBuilder $container): void
            {
                $resolved = [];
                foreach ($configs as $config) {
                    $resolved = array_merge($resolved, $config);
                }

                $container->setParameter('zammad.default_url', 'http://127.0.0.1:8098');

                $url   = $resolved['url']   ?? '%env(default:zammad.default_url:ZAMMAD_URL)%';
                $token = $resolved['token'] ?? '%env(ZAMMAD_TOKEN)%';

                $auth = (new Definition(Auth::class))
                    ->setFactory([Auth::class, 'token'])
                    ->setArguments([$token]);

                $container->setDefinition(
                    ZammadClient::class,
                    (new Definition(ZammadClient::class))
                        ->setFactory([ZammadClient::class, 'create'])
                        ->setArguments([$url, $auth])
                        ->setPublic(true),
                );

                $container->setAlias('zammad_client', ZammadClient::class)->setPublic(true);
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
