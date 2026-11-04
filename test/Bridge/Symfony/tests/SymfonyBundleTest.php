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
 * injected as `env(...)` container parameters rather than through `putenv()`.
 * That alone does not make the cases hermetic: such a parameter is only the
 * fallback Symfony's `EnvVarProcessor` reaches after `$_ENV`, `$_SERVER` and
 * `getenv()`, so a real variable in the developer's shell wins over it. {@see
 * setUp} therefore clears all three for {@see self::ENV_KEYS} and {@see tearDown}
 * puts each back exactly as it was, including the difference between "unset" and
 * "set to an empty string" — otherwise a `ZAMMAD_TOKEN` exported as the cookbook
 * asks decides the outcome of {@see testMissingTokenFailsWithTheLibrarysOwnMessage}.
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

    /**
     * The variables these tests take over, and must hand back untouched.
     */
    private const ENV_KEYS = ['ZAMMAD_URL', 'ZAMMAD_TOKEN'];

    /** @var array<string, mixed> */
    private array $serverBackup = [];

    /** @var array<string, mixed> */
    private array $envBackup = [];

    /** @var array<string, string|false> */
    private array $processEnvBackup = [];

    protected function setUp(): void
    {
        foreach (self::ENV_KEYS as $name) {
            if (array_key_exists($name, $_SERVER)) {
                $this->serverBackup[$name] = $_SERVER[$name];
            }
            if (array_key_exists($name, $_ENV)) {
                $this->envBackup[$name] = $_ENV[$name];
            }
            $this->processEnvBackup[$name] = getenv($name);

            unset($_SERVER[$name], $_ENV[$name]);
            putenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach (self::ENV_KEYS as $name) {
            unset($_SERVER[$name], $_ENV[$name]);

            if (array_key_exists($name, $this->serverBackup)) {
                $_SERVER[$name] = $this->serverBackup[$name];
            }
            if (array_key_exists($name, $this->envBackup)) {
                $_ENV[$name] = $this->envBackup[$name];
            }

            // false means it was unset before; putenv() without '=' unsets again.
            $previous = $this->processEnvBackup[$name] ?? false;
            putenv($previous === false ? $name : "{$name}={$previous}");
        }

        $this->serverBackup = [];
        $this->envBackup = [];
        $this->processEnvBackup = [];
    }

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

        // Both arguments, not just "a client came out": a bundle that read
        // ZAMMAD_URL into the token would still build a ZammadClient, because
        // Auth::token() takes any non-empty string.
        $definition = $container->getDefinition(ZammadClient::class);

        self::assertSame('https://from-env.example', $definition->getArgument(0));
        self::assertSame('from-env', $definition->getArgument(1)->getArgument(0));
        self::assertInstanceOf(ZammadClient::class, $container->get(ZammadClient::class));
    }

    public function testNoZammadSectionMeansNoClientAtAll(): void
    {
        // The compile() helper calls load() by hand, which no application-level
        // path does: Symfony's MergeExtensionConfigurationPass runs an
        // extension only for an alias that has configuration. So an application
        // without config/packages/zammad.yaml gets no ZammadClient, however
        // complete its environment is — the one case the direct load() can
        // never show. The README documents this; this test holds it to it.
        $container = $this->containerWithTheBundleRegistered(
            ['ZAMMAD_URL' => 'https://from-env.example', 'ZAMMAD_TOKEN' => 'from-env'],
        );

        $container->compile(true);

        self::assertFalse($container->has(ZammadClient::class));
    }

    public function testAnEmptyZammadSectionIsEnoughToReadTheEnvironment(): void
    {
        // The other half of the above: the section is required, its keys are
        // not. `zammad: ~` is what the README offers instead of leaving the
        // file out, so it has to carry the environment defaults all the way.
        $container = $this->containerWithTheBundleRegistered(
            ['ZAMMAD_URL' => 'https://from-env.example', 'ZAMMAD_TOKEN' => 'from-env'],
        );

        $container->loadFromExtension('zammad', []);
        $container->compile(true);

        $definition = $container->getDefinition(ZammadClient::class);

        self::assertSame('https://from-env.example', $definition->getArgument(0));
        self::assertSame('from-env', $definition->getArgument(1)->getArgument(0));
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

    /**
     * An uncompiled container that knows the bundle's extension but has not
     * been told anything about `zammad` — the state a Symfony application is in
     * before it reads `config/packages/`.
     *
     * @param array<string, string> $env
     */
    private function containerWithTheBundleRegistered(array $env = []): ContainerBuilder
    {
        $container = new ContainerBuilder();

        foreach ($env as $name => $value) {
            $container->setParameter('env(' . $name . ')', $value);
        }

        $container->registerExtension($this->extension());

        return $container;
    }

    private function extension(): ExtensionInterface
    {
        $extension = (new SymfonyBundle())->getContainerExtension();

        self::assertInstanceOf(ExtensionInterface::class, $extension);

        return $extension;
    }
}
