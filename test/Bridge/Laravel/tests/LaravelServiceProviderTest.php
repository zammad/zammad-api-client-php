<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Bridge\Laravel;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Bridge\LaravelServiceProvider;
use ZammadAPIClient\ZammadClient;

/**
 * Container-level tests for {@see LaravelServiceProvider}.
 *
 * These boot a real {@see Application} and register the provider against it,
 * because the behaviour worth pinning — singleton identity, the config/env
 * precedence documented on the class, and the point at which a missing token
 * surfaces — only exists once Laravel's container resolves the binding.
 *
 * No Zammad instance and no HTTP traffic are involved. `config()` reads from a
 * {@see Repository} bound into the test application; `env()` reads from
 * `$_SERVER`, which {@see setUp} snapshots and {@see tearDown} restores, so the
 * process-global state these helpers depend on does not leak between cases.
 */
#[Group('bridge')]
final class LaravelServiceProviderTest extends TestCase
{
    /**
     * The built-in default, repeated here so a change to it fails loudly.
     *
     * Loopback, which is what keeps the cleartext-credential guard in
     * {@see ZammadClient::create()} from rejecting the plain `http://`.
     */
    private const DEFAULT_URL = 'http://127.0.0.1:8098';

    private const PACKAGED_CONFIG = __DIR__ . '/../../../../config/zammad.php';

    /** @var array<string, mixed> */
    private array $serverBackup = [];

    /** @var list<string> */
    private array $tempPaths = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;

        unset($_SERVER['ZAMMAD_URL'], $_SERVER['ZAMMAD_TOKEN']);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;

        ServiceProvider::$publishes = [];
        ServiceProvider::$publishGroups = [];

        Facade::clearResolvedInstances();
        Container::setInstance(null);

        foreach ($this->tempPaths as $path) {
            @unlink($path . '/bootstrap/cache/config.php');
            @rmdir($path . '/bootstrap/cache');
            @rmdir($path . '/bootstrap');
            @rmdir($path);
        }
        $this->tempPaths = [];
    }

    public function testClientIsRegisteredAsASharedSingleton(): void
    {
        $app = $this->register(['token' => 'from-config']);

        self::assertSame($app->make(ZammadClient::class), $app->make(ZammadClient::class));
    }

    public function testEnvironmentIsReadThroughThePackagedConfig(): void
    {
        // The whole point of merging the packaged config file: .env works
        // without vendor:publish, and the env() call lives in the one place
        // `php artisan config:cache` actually freezes.
        $_SERVER['ZAMMAD_URL'] = 'https://from-env.example';
        $_SERVER['ZAMMAD_TOKEN'] = 'from-env';

        $config = $this->register([])->make('config');

        self::assertSame('https://from-env.example', $config->get('zammad.url'));
        self::assertSame('from-env', $config->get('zammad.token'));
    }

    public function testConfiguredValuesWinOverTheEnvironment(): void
    {
        $_SERVER['ZAMMAD_URL'] = 'https://from-env.example';
        $_SERVER['ZAMMAD_TOKEN'] = 'from-env';

        $config = $this->register([
            'url' => 'https://configured.example',
            'token' => 'from-config',
        ])->make('config');

        self::assertSame('https://configured.example', $config->get('zammad.url'));
        self::assertSame('from-config', $config->get('zammad.token'));
    }

    public function testBuiltInDefaultUrlAppliesWhenTheEnvironmentIsSilent(): void
    {
        $_SERVER['ZAMMAD_TOKEN'] = 'from-env';

        $app = $this->register([]);

        self::assertSame(self::DEFAULT_URL, $app->make('config')->get('zammad.url'));
        self::assertInstanceOf(ZammadClient::class, $app->make(ZammadClient::class));
    }

    public function testPackagedDefaultsAreNotMergedWhenConfigurationIsCached(): void
    {
        // With a cached configuration Laravel never loads .env, so re-reading
        // the environment here would hand back defaults instead of the values
        // the application was cached with. mergeConfigFrom() must stay out of
        // the way and let the cached configuration stand.
        $_SERVER['ZAMMAD_URL'] = 'https://from-env.example';

        $config = $this->register(['token' => 'from-cache'], cached: true)->make('config');

        self::assertNull($config->get('zammad.url'));
        self::assertSame('from-cache', $config->get('zammad.token'));
    }

    public function testExplicitlyEmptyUrlFallsBackToTheBuiltInDefault(): void
    {
        // An empty string counts as unset. Were it passed through, the client
        // would reject it while constructing.
        $app = $this->register(['url' => '', 'token' => 'from-config']);

        self::assertInstanceOf(ZammadClient::class, $app->make(ZammadClient::class));
    }

    public function testNonStringConfigValueDoesNotReachTheClientAsTypeError(): void
    {
        // Repository::get() is typed mixed and config/zammad.php is a plain PHP
        // file the application owns. A wrong type must not surface as a
        // TypeError thrown from somewhere deep inside the client.
        $app = $this->register(['url' => ['not', 'a', 'string'], 'token' => 'from-config']);

        self::assertInstanceOf(ZammadClient::class, $app->make(ZammadClient::class));
    }

    public function testNonStringTokenFailsWithTheLibrarysOwnMessage(): void
    {
        $app = $this->register(['token' => ['not', 'a', 'string']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API token must not be empty.');

        $app->make(ZammadClient::class);
    }

    public function testMissingTokenFailsWithTheLibrarysOwnMessage(): void
    {
        $app = $this->register([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('API token must not be empty.');

        $app->make(ZammadClient::class);
    }

    public function testNothingIsBuiltUntilTheClientIsResolved(): void
    {
        // Registering without any credentials must not throw. The binding is a
        // singleton closure, so an application that never asks for the client
        // never pays for it — and never fails to boot because of it.
        $app = $this->register([]);

        self::assertTrue($app->bound(ZammadClient::class));
    }

    public function testBootPublishesThePackagedConfigUnderItsTag(): void
    {
        $app = $this->register(['token' => 'from-config']);
        (new LaravelServiceProvider($app))->boot();

        $paths = ServiceProvider::pathsToPublish(LaravelServiceProvider::class, 'zammad-config');

        self::assertCount(1, $paths);
        self::assertSame(
            realpath(self::PACKAGED_CONFIG),
            realpath((string) array_key_first($paths)),
        );
        self::assertSame($app->configPath('zammad.php'), reset($paths));
    }

    public function testPackagedConfigDefaultsMatchTheProvider(): void
    {
        // The provider's own fallback and the packaged file must not drift: a
        // published config would otherwise silently change the default.
        $defaults = require self::PACKAGED_CONFIG;

        self::assertSame(self::DEFAULT_URL, $defaults['url']);
        self::assertSame('', $defaults['token']);
    }

    /**
     * Boots a throwaway application with the given `zammad` configuration and
     * runs the provider's register() against it.
     *
     * `$cached` writes the marker file Laravel checks in
     * `Application::configurationIsCached()`, which is the only way to observe
     * the branch `mergeConfigFrom()` takes in a cached installation.
     *
     * @param array<string, mixed> $zammadConfig
     */
    private function register(array $zammadConfig, bool $cached = false): Application
    {
        $basePath = sys_get_temp_dir() . '/zammad-bridge-' . bin2hex(random_bytes(6));
        mkdir($basePath . '/bootstrap/cache', 0o777, true);
        $this->tempPaths[] = $basePath;

        if ($cached) {
            file_put_contents($basePath . '/bootstrap/cache/config.php', '<?php return [];');
        }

        $app = new Application($basePath);
        $app->instance('config', new Repository(['zammad' => $zammadConfig]));

        (new LaravelServiceProvider($app))->register();

        return $app;
    }
}
