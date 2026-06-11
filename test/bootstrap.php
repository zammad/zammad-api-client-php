<?php
declare(strict_types=1);

$composerAutoloadFiles = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/../../../.composer/autoload.php',
];

foreach ($composerAutoloadFiles as $autoloadFile) {
    if (!is_file($autoloadFile)) {
        continue;
    }

    $loader = require $autoloadFile;
    $loader->add('ZammadAPIClient', __DIR__);

    return;
}

$systemAutoloadFiles = [
    '/usr/share/php/GuzzleHttp/autoload.php',
    '/usr/share/php/Mockery/autoload.php',
    '/usr/share/php/Psr/Http/Client/autoload.php',
    '/usr/share/php/Psr/Http/Message/autoload.php',
    '/usr/share/php/Psr/Log/autoload.php',
];

foreach ($systemAutoloadFiles as $autoloadFile) {
    if (is_file($autoloadFile)) {
        require_once $autoloadFile;
    }
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'ZammadAPIClient\\')) {
        return;
    }

    $relativeClass = str_replace('\\', '/', substr($class, strlen('ZammadAPIClient\\')));
    $relativePath = $relativeClass . '.php';
    $candidateFiles = [
        __DIR__ . '/../src/' . $relativePath,
        __DIR__ . '/ZammadAPIClient/' . $relativePath,
    ];

    foreach ($candidateFiles as $candidateFile) {
        if (!is_file($candidateFile)) {
            continue;
        }

        require_once $candidateFile;

        return;
    }
});

return;
