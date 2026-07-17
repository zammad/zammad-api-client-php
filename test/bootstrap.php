<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

$autoloadFile = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoloadFile)) {
    throw new RuntimeException(
        'Composer autoloader not found. Run "composer install" first.',
    );
}

/** @var ClassLoader $loader */
$loader = require $autoloadFile;
$loader->add('ZammadAPIClient\\Tests\\', __DIR__);
