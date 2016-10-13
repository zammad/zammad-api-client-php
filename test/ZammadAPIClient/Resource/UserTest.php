<?php

namespace ZammadAPIClient\Resource;

use ZammadAPIClient\ResourceType;

class UserTest extends AbstractBaseTest
{
    protected $resource_type = ResourceType::USER;
    protected $update_field  = 'firstname';

    public function objectCreateProvider()
    {
        $configs = [
            // Minimum valid object data.
            [
                'values' => [
                    'login' => 'unittest1' . $this->getUniqueID() . '@example.com',
                    'email' => 'unittest1' . $this->getUniqueID() . '@example.com',
                ],
                'expected_success' => true,
            ],
            // Another object with valid data.
            [
                'values' => [
                    'login' => 'unittest2' . $this->getUniqueID() . '@example.com',
                    'email' => 'unittest2' . $this->getUniqueID() . '@example.com',
                ],
                'expected_success' => true,
            ],
            // Missing required fields.
            [
                'values' => [
                    // 'login' => 'unittest3' . $this->getUniqueID() . '@example.com',
                    // 'email' => 'unittest3' . $this->getUniqueID() . '@example.com',
                    'first_name' => 'Unit test user 3',
                ],
                'expected_success' => false,
            ],
        ];

        return $configs;
    }
}
