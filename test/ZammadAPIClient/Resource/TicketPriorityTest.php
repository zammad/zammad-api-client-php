<?php

namespace ZammadAPIClient\Resource;

use ZammadAPIClient\ResourceType;

class TicketPriorityTest extends AbstractBaseTest
{
    protected $resource_type = ResourceType::TICKET_PRIORITY;
    protected $update_field  = 'note';

    public function objectCreateProvider()
    {
        $configs = [
            // Minimum valid object data.
            [
                'values' => [
                    'name'   => 'Unit test ticket priority 1 ' . $this->getUniqueID(),
                ],
                'expected_success' => true,
            ],
            // Another object with valid data.
            [
                'values' => [
                    'name'   => 'Unit test ticket priority 2 ' . $this->getUniqueID(),
                ],
                'expected_success' => true,
            ],
            // Missing required fields.
            [
                'values' => [
                    // 'name'   => 'Unit test ticket priority 3 ' . $this->getUniqueID(),
                    'note' => 'Unit test ticket priority 3...',
                ],
                'expected_success' => false,
            ],
        ];

        return $configs;
    }
}
