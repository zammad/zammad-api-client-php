<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

$autoloadFiles = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
];

foreach ($autoloadFiles as $file) {
    if (file_exists($file)) {
        /** @var ClassLoader $loader */
        $loader = require $file;
        $loader->add('ZammadAPIClient\\Tests\\', __DIR__);
        return;
    }
}

throw new RuntimeException('Could not find Composer autoloader.');
