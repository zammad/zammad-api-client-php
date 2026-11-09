<?php

declare(strict_types=1);

/**
 * Builds a Symfony Flex recipe endpoint from src/Bridge/Symfony/recipe/.
 *
 * Flex reads recipes from an endpoint, never from the package itself: an
 * `index.json` listing the recipe versions, plus one JSON file per version
 * that carries the manifest and the files to copy. The release workflow
 * attaches both to every GitHub release, so that applications can point
 * `extra.symfony.endpoint` at `releases/latest/download/index.json`.
 *
 * The recipe source keeps the `symfony/recipes-contrib` layout
 * (`<version>/manifest.json`, `<version>/config/...`), so it can be
 * contributed there unchanged.
 *
 * Usage: php bin/build-flex-endpoint.php <output dir> <base url>
 *
 * The base URL is where the generated files will be served from, e.g.
 * `https://github.com/zammad/zammad-api-client-php/releases/latest/download`.
 */

const PACKAGE = 'zammad/zammad-api-client-php';
const SOURCE  = __DIR__ . '/../src/Bridge/Symfony/recipe';

[, $outDir, $baseUrl] = $argv + [null, null, null];
if ($outDir === null || $baseUrl === null) {
    fwrite(STDERR, "Usage: php bin/build-flex-endpoint.php <output dir> <base url>\n");
    exit(2);
}

if (!is_dir($outDir) && !mkdir($outDir, 0777, true)) {
    fwrite(STDERR, "Cannot create $outDir\n");
    exit(1);
}

$dotted   = str_replace('/', '.', PACKAGE);
$versions = [];

foreach (glob(SOURCE . '/*/manifest.json') ?: [] as $manifestFile) {
    $dir     = dirname($manifestFile);
    $version = basename($dir);

    $files = [];
    $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($entries as $entry) {
        $relative = substr($entry->getPathname(), strlen($dir) + 1);
        if ($relative === 'manifest.json') {
            continue;
        }
        $files[$relative] = [
            'contents'   => explode("\n", (string) file_get_contents($entry->getPathname())),
            'executable' => false,
        ];
    }
    ksort($files);

    $recipe = [
        'manifests' => [
            PACKAGE => [
                'manifest' => json_decode((string) file_get_contents($manifestFile), true, 512, JSON_THROW_ON_ERROR),
                'files'    => $files,
                // Flex records this in symfony.lock to tell later recipe updates apart.
                'ref'      => sha1(json_encode([$version, file_get_contents($manifestFile), $files], JSON_THROW_ON_ERROR)),
            ],
        ],
    ];

    write("$outDir/$dotted.$version.json", $recipe);
    $versions[] = $version;
}

if ($versions === []) {
    fwrite(STDERR, 'No recipe found under ' . SOURCE . "\n");
    exit(1);
}
usort($versions, 'version_compare');

write("$outDir/index.json", [
    'recipes'    => [PACKAGE => $versions],
    'branch'     => 'main',
    // Not a contrib endpoint: the application opted in by adding it, so Flex
    // must apply the recipe without asking for `allow-contrib`. With `true`
    // a non-interactive install would skip it without a word.
    'is_contrib' => false,
    '_links'     => [
        'repository'      => 'github.com/zammad/zammad-api-client-php',
        'origin_template' => '{package}:{version}@github.com/zammad/zammad-api-client-php:releases',
        'recipe_template' => rtrim($baseUrl, '/') . '/{package_dotted}.{version}.json',
    ],
]);

echo implode("\n", array_map(static fn (string $v): string => "$outDir/$dotted.$v.json", $versions)), "\n$outDir/index.json\n";

/**
 * @param array<string, mixed> $data
 */
function write(string $file, array $data): void
{
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
}
