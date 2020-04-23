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

    public function testImport()
    {
        $users_csv_string = $this->getTestFileContent('users_import.csv');

        $object = self::getClient()->resource( $this->resource_type )
            ->import($users_csv_string);

        $this->assertInstanceOf(
            $this->resource_type,
            $object
        );

        $objects = self::getClient()->resource( $this->resource_type )->all();
        $this->assertTrue(
            is_array($objects) && count($objects),
            'Requesting all users must return data.'
        );

        $changed_object_found = false;
        $created_object_found = false;

        foreach ($objects as $object) {
            if (
                $object->getID() == 2
                && $object->getValue('login') == 'nicole.braun@zammad.org'
                && $object->getValue('department') == 'ut1 - Unit test 1'
            ) {
                $changed_object_found = true;
                continue;
            }

            if (
                $object->getValue('login') == 'ut2user@example.com'
                && $object->getValue('department') == 'ut2 - Unit test 2'
            ) {
                $created_object_found = true;
                continue;
            }

        }

        $this->assertTrue(
            $changed_object_found,
            'Changed user with ID 2 must be found and have correct values set.'
        );

        $this->assertTrue(
            $created_object_found,
            'Newly created user must be found and have correct values set.'
        );
    }
}
