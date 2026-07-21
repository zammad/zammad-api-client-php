<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function chdir;
use function dirname;
use function exec;
use function getcwd;
use function sprintf;

#[Group('integration')]
final class CookbookIntegrationTest extends TestCase
{
    public function testAllCookbookRecipesRunSuccessfully(): void
    {
        $url   = getenv('ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL') ?: null;
        $token = getenv('ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_TOKEN') ?: null;

        if ($url === null || $token === null) {
            self::markTestSkipped(
                'Integration tests require ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL and _TOKEN.',
            );
        }

        $recipeDir = dirname(__DIR__, 2) . '/examples/cookbook';
        $recipeFiles = glob($recipeDir . '/0[1-9]*.php') ?: [];

        self::assertNotEmpty($recipeFiles, 'No cookbook recipes found.');

        $cwd = getcwd();
        $projectRoot = dirname(__DIR__, 2);
        chdir((string) $projectRoot);

        foreach ($recipeFiles as $file) {
            $command = sprintf(
                'ZAMMAD_URL=%s ZAMMAD_TOKEN=%s php %s 2>&1',
                escapeshellarg($url),
                escapeshellarg($token),
                escapeshellarg($file),
            );

            exec($command, $output, $exitCode);

            self::assertSame(
                0,
                $exitCode,
                sprintf(
                    "Recipe %s failed with exit code %d:\n%s",
                    basename($file),
                    $exitCode,
                    implode("\n", $output),
                ),
            );
        }

        chdir((string) $cwd);
    }
}
