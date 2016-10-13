<?php

function IncludeIfExists($File)
{
    if ( file_exists($File) ) {
        return include $File;
    }
}

if (
    ( !$Loader = IncludeIfExists( __DIR__.'/../vendor/autoload.php' ) )
    && ( !$Loader = IncludeIfExists( __DIR__.'/../../../.composer/autoload.php' ) )
) {
    die(
        'You must set up the project dependencies, run the following commands:' . PHP_EOL
        . 'curl -s http://getcomposer.org/installer | php' . PHP_EOL
        . 'php composer.phar install' . PHP_EOL
    );
}

$Loader->add( 'ZammadAPIClient', __DIR__ );
return $Loader;
