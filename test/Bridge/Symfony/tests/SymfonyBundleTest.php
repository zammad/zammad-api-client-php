<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Bridge\Symfony;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use ZammadAPIClient\Bridge\SymfonyBundle;
use ZammadAPIClient\Core\Auth;
use ZammadAPIClient\ZammadClient;

/**
 * Container-level tests for {@see SymfonyBundle}.
 *
 * These drive the bundle's extension through a real {@see ContainerBuilder} —
 * load, compile and dump — because that is the path a Symfony application takes
 * when it warms `var/cache`, and the path on which the bundle has broken before:
 * registering a live instance rather than a definition made every compiled
 * container fail with *"has a dependency on a non-existent service"*.
 *
 * No Zammad instance and no HTTP traffic are involved. Environment variables are
 * injected as `env(...)` container parameters rather than through `putenv()`, so
 * the cases stay hermetic and order-independent.
 */
#[Group('bridge')]
final class SymfonyBundleTest extends TestCase
{
    /**
     * The built-in default, repeated here so a change to it fails loudly.
     *
     * It is a loopback address, which is what keeps the cleartext-credential
     * guard in {@see ZammadClient::create()} from rejecting the plain `http://`.
     */
    private const DEFAULT_URL = 'http://127.0.0.1:8098';

    public function testExtensionAliasBindsTheZammadConfigurationKey(): void
    {
        self::assertSame('zammad', $this->extension()->getAlias());
    }

    public function testExtensionDeclaresNoXmlConfiguration(): void
    {
        // The bundle configures itself in PHP, so there is no XSD to validate
        // against and no namespace to register. Empty strings are how an
        // extension says so; a future Configuration tree would change this.
        self::assertSame('', $this->extension()->getNamespace());
        self::assertSame('', $this->extension()->getXsdValidationBasePath());
    }

    public function testClientIsRegisteredAndPubliclyAliased(): void
    {
        $container = $this->compile(['token' => 'from-config']);

        self::assertTrue($container->has(ZammadClient::class));
        self::assertTrue($container->has('zammad_client'));
        self::assertSame(
            $container->get(ZammadClient::class),
            $container->get('zammad_client'),
        );
    }

    public function testClientIsRegisteredAsALazyFactoryDefinition(): void
    {
        $container = new ContainerBuilder();
        $this->extension()->load([['token' => 'from-config']], $container);

        $definition = $container->getDefinition(ZammadClient::class);

        self::assertSame([ZammadClient::class, 'create'], $definition->getFactory());
        self::assertTrue($definition->isPublic());

        // The credentials must be a nested definition, not a constructed Auth:
        // anything instantiated during load() is written into the dumped
        // container instead of being resolved at runtime.
        $auth = $definition->getArgument(1);
        self::assertInstanceOf(\Symfony\Component\DependencyInjection\Definition::class, $auth);
        self::assertSame([Auth::class, 'token'], $auth->getFactory());
    }

    public function testCompiledContainerCanBeDumped(): void
    {
        // The regression guard. Dumping is what `cache:clear` does, and it is
        // where a non-dumpable service blows up — including for applications
        // that never resolve the client themselves.
        $container = $this->compile(['token' => 'from-config']);

        $dumped = (new PhpDumper($container))->dump(['class' => 'ZammadTestContainer']);

        self::assertIsString($dumped);
        self::assertStringContainsString('ZammadTestContainer', $dumped);
    }

    public function testConfiguredValuesWinOverEnvironmentDefaults(): void
    {
        $container = $this->compile(
            ['url' => 'https://configured.example', 'token' => 'from-config'],
            ['ZAMMAD_URL' => 'https://from-env.example', 'ZAMMAD_TOKEN' => 'from-env'],
        );

        $definition = $container->getDefinition(ZammadClient::class);

        self::assertSame('https://configured.example', $definition->getArgument(0));
        self::assertSame('from-config', $definition->getArgument(1)->getArgument(0));
    }

    public function testEnvironmentVariablesAreUsedWhenNothingIsConfigured(): void
    {
        $container = $this->compile(
            [],
            ['ZAMMAD_URL' => 'https://from-env.example', 'ZAMMAD_TOKEN' => 'from-env'],
        );

        self::assertInstanceOf(ZammadClient::class, $container->get(ZammadClient::class));
    }

    public function testDefaultUrlAppliesWhenNeitherConfigNorEnvironmentSetsOne(): void
    {
        $container = $this->compile(['token' => 'from-config']);

        self::assertSame(self::DEFAULT_URL, $container->getParameter('zammad.default_url'));
        self::assertInstanceOf(ZammadClient::class, $container->get(ZammadClient::class));
    }

    public function testMissingTokenFailsWithTheLibrarysOwnMessage(): void
    {
        // Not Symfony's EnvNotFoundException: an unset ZAMMAD_TOKEN falls back to
        // an empty string, so the error names the actual problem — and matches
        // what LaravelServiceProvider does.
        $container = $this->compile([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API token must not be empty.');

        $container->get(ZammadClient::class);
    }

    public function testNothingIsInstantiatedWhileTheContainerIsCompiled(): void
    {
        // Compiling with no token at all must succeed; only resolving the
        // service may fail. Before the client became a lazy definition this
        // broke `cache:clear`, and with it `composer install`.
        $container = $this->compile([]);

        self::assertTrue($container->has(ZammadClient::class));
    }

    /**
     * @param array<string, string> $config
     * @param array<string, string> $env
     */
    private function compile(array $config, array $env = []): ContainerBuilder
    {
        $container = new ContainerBuilder();

        foreach ($env as $name => $value) {
            $container->setParameter('env(' . $name . ')', $value);
        }

        $this->extension()->load([$config], $container);
        $container->compile(true);

        return $container;
    }

    private function extension(): ExtensionInterface
    {
        $extension = (new SymfonyBundle())->getContainerExtension();

        self::assertInstanceOf(ExtensionInterface::class, $extension);

        return $extension;
    }
}
