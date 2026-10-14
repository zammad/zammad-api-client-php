<?php

/**
 * Zammad API Client — Slim 4 setup (PHP-DI container).
 *
 * There is no Slim bridge, and none is needed: the client is a plain object, so
 * one container definition is enough. The same shape works for any PSR-11
 * container — League Container, Pimple, Laminas ServiceManager — only the
 * definition syntax differs.
 *
 * Requirements:
 *   composer require slim/slim slim/psr7 php-di/php-di zammad/zammad-api-client-php
 *
 * ZAMMAD_URL and ZAMMAD_TOKEN come from the environment — your php-fpm pool, your
 * container, or `export` in the shell for the built-in server. Read with getenv(),
 * like the other recipes: $_ENV is empty unless php.ini's variables_order includes
 * E, and Slim loads no .env file of its own.
 *
 *   export ZAMMAD_URL=https://zammad.example
 *   export ZAMMAD_TOKEN=xxx
 *
 * public/index.php:
 *
 *   use DI\ContainerBuilder;
 *   use Slim\Factory\AppFactory;
 *   use ZammadAPIClient\Core\Auth;
 *   use ZammadAPIClient\ZammadClient;
 *
 *   $builder = new ContainerBuilder();
 *   $builder->addDefinitions([
 *       ZammadClient::class => fn (): ZammadClient => ZammadClient::create(
 *           getenv('ZAMMAD_URL') ?: 'http://localhost:3000',
 *           Auth::token(getenv('ZAMMAD_TOKEN') ?: ''),
 *       ),
 *   ]);
 *
 *   AppFactory::setContainer($builder->build());
 *   $app = AppFactory::create();
 *   $app->addErrorMiddleware(true, true, true);
 *
 *   $app->get('/tickets/{id}', ShowTicket::class);
 *   $app->run();
 *
 * The action gets the client injected — PHP-DI autowires the constructor, so
 * nothing else has to know where the client comes from:
 *
 *   use Psr\Http\Message\ResponseInterface;
 *   use Psr\Http\Message\ServerRequestInterface;
 *   use ZammadAPIClient\Exceptions\NotFoundException;
 *   use ZammadAPIClient\ZammadClient;
 *
 *   final class ShowTicket
 *   {
 *       public function __construct(private ZammadClient $zammad) {}
 *
 *       public function __invoke(
 *           ServerRequestInterface $request,
 *           ResponseInterface $response,
 *           array $args,
 *       ): ResponseInterface {
 *           try {
 *               $ticket = $this->zammad->ticket()->find((int) $args['id']);
 *           } catch (NotFoundException) {
 *               return $response->withStatus(404);
 *           }
 *
 *           $response->getBody()->write((string) json_encode($ticket->toArray()));
 *
 *           return $response->withHeader('Content-Type', 'application/json');
 *       }
 *   }
 *
 * Definitions are lazy: the client is built the first time something asks for
 * it, not on every request. Because Auth rejects an empty token, a missing
 * ZAMMAD_TOKEN surfaces as an InvalidArgumentException at that point rather
 * than as a confusing 401 later.
 *
 * To use a different HTTP client here, return a client built from your own
 * RequestHandler instead — see 07-custom-http-client.php.
 */

declare(strict_types=1);

// Wiring happens in your Slim application — this file is for reference.
fwrite(STDOUT, "See docblock above for Slim 4 setup instructions.\n");
