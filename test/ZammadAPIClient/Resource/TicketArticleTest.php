<?php

namespace ZammadAPIClient\Resource;

use ZammadAPIClient\ResourceType;

class TicketArticleTest extends AbstractBaseTest
{
    protected $resource_type = ResourceType::TICKET_ARTICLE;
    protected $update_field  = 'body';

    public function objectCreateProvider()
    {
        $configs = [
            // Minimum valid object data.
            [
                'values' => [
                    'subject'   => 'Unit test ticket article 1' . $this->getUniqueID(),
                    'body'      => 'Unit test ticket article 1...' . $this->getUniqueID(),
                    'ticket_id' => 1,
                ],
                'expected_success' => true,
            ],
            // Another object with valid data.
            [
                'values' => [
                    'subject'   => 'Unit test ticket article 2' . $this->getUniqueID(),
                    'body'      => 'Unit test ticket article 2...' . $this->getUniqueID(),
                    'ticket_id' => 1,
                ],
                'expected_success' => true,
            ],
            // Missing required fields.
            [
                'values' => [
                    // 'subject' => 'Unit test ticket article 3 ' . $this->getUniqueID(),
                    // 'body'      => 'Unit test ticket article 3...' . $this->getUniqueID(),
                    'ticket_id' => 1,
                ],
                'expected_success' => false,
            ],
        ];

        return $configs;
    }
}
