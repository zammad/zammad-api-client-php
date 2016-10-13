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
}
