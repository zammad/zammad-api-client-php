<?php

namespace ZammadAPIClient\Resource;

use ZammadAPIClient\ResourceType;

class TicketStateTest extends AbstractBaseTest
{
    protected $resource_type = ResourceType::TICKET_STATE;
    protected $update_field  = 'note';

    public function objectCreateProvider()
    {
        $configs = [
            // Minimum valid object data.
            [
                'values' => [
                    'name'          => 'Unit test ticket state 1 ' . $this->getUniqueID(),
                    'state_type_id' => 1,
                ],
                'expected_success' => true,
            ],
            // Another object with valid data.
            [
                'values' => [
                    'name'          => 'Unit test ticket state 2 ' . $this->getUniqueID(),
                    'state_type_id' => 1,
                ],
                'expected_success' => true,
            ],
            // Missing required fields.
            [
                'values' => [
                    // 'name'          => 'Unit test ticket state 3 ' . $this->getUniqueID(),
                    // 'state_type_id' => 1,
                    'note' => 'Unit test ticket state 3...',
                ],
                'expected_success' => false,
            ],
        ];

        return $configs;
    }
}
