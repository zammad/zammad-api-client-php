<?php

namespace ZammadAPIClient\Resource;

use ZammadAPIClient\ResourceType;

class OrganizationTest extends AbstractBaseTest
{
    protected $resource_type = ResourceType::ORGANIZATION;
    protected $update_field  = 'name';

    public function objectCreateProvider()
    {
        $configs = [
            // Minimum valid object data.
            [
                'values' => [
                    'name' => 'Unit test organization 1 ' . $this->getUniqueID(),
                ],
                'expected_success' => true,
            ],
            // Another object with valid data.
            [
                'values' => [
                    'name' => 'Unit test organization 2 ' . $this->getUniqueID(),
                ],
                'expected_success' => true,
            ],
            // Missing required field 'name'.
            [
                'values' => [
                    // 'name' => 'Unit test organization 3 ' . $this->getUniqueID(),
                    'note' => 'Unit test organization 3',
                ],
                'expected_success' => false,
            ],
        ];

        return $configs;
    }

    public function testImport()
    {
        $organizations_csv_string = $this->getTestFileContent('organizations_import.csv');

        $object = self::getClient()->resource( $this->resource_type )
            ->import($organizations_csv_string);

        $this->assertInstanceOf(
            $this->resource_type,
            $object
        );

        $objects = self::getClient()->resource( $this->resource_type )->all();
        $this->assertTrue(
            is_array($objects) && count($objects),
            'Requesting all organizations must return data.'
        );

        $changed_object_found = false;
        $created_object_found = false;

        foreach ($objects as $object) {
            if (
                $object->getID() == 1
                && $object->getValue('name') == 'Zammad Foundation'
                && $object->getValue('note') == 'ut1 note'
            ) {
                $changed_object_found = true;
                continue;
            }

            if (
                $object->getValue('name') == 'ut2 - Unit test 2'
                && $object->getValue('note') == 'ut2 note'
            ) {
                $created_object_found = true;
                continue;
            }

        }

        $this->assertTrue(
            $changed_object_found,
            'Changed organization with ID 1 must be found and have correct values set.'
        );

        $this->assertTrue(
            $created_object_found,
            'Newly created organization must be found and have correct values set.'
        );
    }
}
