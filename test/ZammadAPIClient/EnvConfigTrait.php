<?php

namespace ZammadAPIClient;

trait EnvConfigTrait
{
    private static function getZammadConfig(array $defaults = []): array
    {
        $config = $defaults;

        $env_keys = [
            'url'      => 'ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL',
            'username' => 'ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_USERNAME',
            'password' => 'ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_PASSWORD',
        ];

        foreach ($env_keys as $config_key => $env_key) {
            $value = getenv($env_key);
            if (empty($value)) {
                throw new \RuntimeException("Missing environment variable $env_key");
            }
            $config[$config_key] = $value;
        }

        return $config;
    }

    private static function createZammadClient(array $extra_config = []): ?Client
    {
        try {
            $config = self::getZammadConfig($extra_config);
        }
        catch (\RuntimeException $e) {
            return null;
        }

        return new Client($config);
    }
}
