<?php

namespace ZammadAPIClient\Resource;

use ZammadAPIClient\ResourceType;

class GroupTest extends AbstractBaseTest
{
    protected $resource_type = ResourceType::GROUP;
    protected $update_field  = 'note';

    public function objectCreateProvider()
    {
        $configs = [
            // Minimum valid object data.
            [
                'values' => [
                    'name' => 'Unit test group 1 ' . $this->getUniqueID(),
                ],
                'expected_success' => true,
            ],
            // Another object with valid data.
            [
                'values' => [
                    'name' => 'Unit test group 2 ' . $this->getUniqueID(),
                ],
                'expected_success' => true,
            ],
            // Missing required fields.
            [
                'values' => [
                    // 'name' => 'Unit test group 3 ' . $this->getUniqueID(),
                    'note' => 'Unit test group 3',
                ],
                'expected_success' => false,
            ],
        ];

        return $configs;
    }
}
