<?php

namespace ZammadAPIClient\Resource;

use ZammadAPIClient\ResourceType;

class TextModuleTest extends AbstractBaseTest
{
    protected $resource_type = ResourceType::TEXT_MODULE;
    protected $update_field  = 'content';

    public function objectCreateProvider()
    {
        $configs = [
            // Minimum valid object data.
            [
                'values' => [
                    'name'    => 'Unit test text module 1 name ' . $this->getUniqueID(),
                    'content' => 'Unit test text module 1 content ' . $this->getUniqueID(),
                ],
                'expected_success' => true,
            ],
            // Another object with valid data.
            [
                'values' => [
                    'name'    => 'Unit test text module 2 name ' . $this->getUniqueID(),
                    'content' => 'Unit test text module 2 content ' . $this->getUniqueID(),
                ],
                'expected_success' => true,
            ],
            // Missing required fields.
            [
                'values' => [
                    // 'name' => 'Unit test text module 3 name ' . $this->getUniqueID(),

                    'content' => 'Unit test text module 3 content ' . $this->getUniqueID(),
                ],
                'expected_success' => false,
            ],
        ];

        return $configs;
    }

    public function testImport()
    {
        $text_modules_csv_string = $this->getTestFileContent('text_modules_import.csv');

        $object = self::getClient()->resource( $this->resource_type )
            ->import($text_modules_csv_string);

        $this->assertInstanceOf(
            $this->resource_type,
            $object
        );

        $objects = self::getClient()->resource( $this->resource_type )->all();
        $this->assertTrue(
            is_array($objects) && count($objects),
            'Requesting all text modules must return data.'
        );

        $changed_object_found = false;
        $created_object_found = false;

        foreach ($objects as $object) {
            if (
                $object->getID() == 1
                && $object->getValue('name') == 'ut1 - Unit test 1'
                && $object->getValue('content') == 'Unit test 1'
            ) {
                $changed_object_found = true;
                continue;
            }

            if (
                $object->getValue('name') == 'ut2 - Unit test 2'
                && $object->getValue('content') == 'Unit test 2'
            ) {
                $created_object_found = true;
                continue;
            }

        }

        $this->assertTrue(
            $changed_object_found,
            'Changed text module with ID 1 must be found and have correct values set.'
        );

        $this->assertTrue(
            $created_object_found,
            'Newly created text module must be found and have correct values set.'
        );
    }
}
