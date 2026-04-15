<?php

namespace ZammadAPIClient\Resource;

use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Client;
use ZammadAPIClient\ResourceType;

class LinkTest extends TestCase
{
    private static $client;
    private static $source_ticket;
    private static $target_ticket;

    public static function setUpBeforeClass(): void
    {
        $client_config = [];

        $env_keys = [
            'url'      => 'ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL',
            'username' => 'ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_USERNAME',
            'password' => 'ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_PASSWORD',
        ];
        foreach ( $env_keys as $config_key => $env_key ) {
            $value = getenv( $env_key );
            if ( empty($value) ) {
                throw new \RuntimeException("Missing environment variable $env_key");
            }

            $client_config[$config_key] = $value;
        }

        self::$client = new Client($client_config);
    }

    public function setUp(): void
    {
        parent::setUp();
        self::createTickets();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        self::deleteTickets();
    }

    public static function getClient()
    {
        return self::$client;
    }

    protected static function getUniqueID()
    {
        return uniqid('', true);
    }

    private static function createTickets()
    {
        self::$source_ticket = self::getClient()->resource(ResourceType::TICKET);
        self::$source_ticket->setValues([
            'group_id'    => 1,
            'priority_id' => 1,
            'state_id'    => 1,
            'title'       => 'Unit test link source ticket ' . self::getUniqueID(),
            'customer_id' => 1,
            'article'     => [
                'subject' => 'Unit test article 1 ' . self::getUniqueID(),
                'body'    => 'Unit test article 1... ' . self::getUniqueID(),
            ],
        ]);
        self::$source_ticket->save();

        self::$target_ticket = self::getClient()->resource(ResourceType::TICKET);
        self::$target_ticket->setValues([
            'group_id'    => 1,
            'priority_id' => 1,
            'state_id'    => 1,
            'title'       => 'Unit test link target ticket ' . self::getUniqueID(),
            'customer_id' => 1,
            'article'     => [
                'subject' => 'Unit test article 2 ' . self::getUniqueID(),
                'body'    => 'Unit test article 2... ' . self::getUniqueID(),
            ],
        ]);
        self::$target_ticket->save();
    }

    private static function deleteTickets()
    {
        if (!empty(self::$source_ticket)) {
            self::$source_ticket->delete();
        }
        if (!empty(self::$target_ticket)) {
            self::$target_ticket->delete();
        }
    }

    public function testGetWithoutObjectId()
    {
        $link = self::getClient()->resource(ResourceType::LINK);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing object ID');

        $link->get('', 'Ticket');
    }

    public function testGetWithInvalidObjectId()
    {
        $link = self::getClient()->resource(ResourceType::LINK);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Missing object ID');

        $link->get(0, 'Ticket');
    }

    public function testGet()
    {
        $link = self::getClient()->resource(ResourceType::LINK);
        $result = $link->get(self::$source_ticket->getID(), 'Ticket');

        $this->assertSame($link, $result);
        $this->assertFalse($link->hasError());
    }

    public function testGetByTicketId()
    {
        $link = self::getClient()->resource(ResourceType::LINK);
        $result = $link->get(self::$source_ticket->getID());

        $this->assertSame($link, $result);
        $this->assertFalse($link->hasError());
    }

    public function testAddWithoutSourceTicket()
    {
        $link = self::getClient()->resource(ResourceType::LINK);

        $this->expectException(\TypeError::class);

        $link->add(null, null);
    }

    public function testAddWithoutTargetTicket()
    {
        $link = self::getClient()->resource(ResourceType::LINK);

        $this->expectException(\TypeError::class);

        $link->add(self::$source_ticket, null);
    }

    public function testAdd()
    {
        $link = self::getClient()->resource(ResourceType::LINK);
        $result = $link->add(self::$source_ticket, self::$target_ticket, 'normal');

        $this->assertSame($link, $result);
        $this->assertFalse($link->hasError());
    }

    public function testAddWithParentLinkType()
    {
        $link = self::getClient()->resource(ResourceType::LINK);
        $result = $link->add(self::$source_ticket, self::$target_ticket, 'parent');

        $this->assertSame($link, $result);
        $this->assertFalse($link->hasError());
    }

    public function testAddWithChildLinkType()
    {
        $link = self::getClient()->resource(ResourceType::LINK);
        $result = $link->add(self::$source_ticket, self::$target_ticket, 'child');

        $this->assertSame($link, $result);
        $this->assertFalse($link->hasError());
    }

    public function testAddWithInvalidLinkType()
    {
        $link = self::getClient()->resource(ResourceType::LINK);
        $link->add(self::$source_ticket, self::$target_ticket, 'invalid_type');

        $this->assertNotEmpty($link->getError());
        $this->assertSame('Linktype is not supported.', $link->getError());
    }

    public function testAddWithInvalidTickets()
    {
        $source_ticket = self::getClient()->resource(ResourceType::TICKET);
        $target_ticket = self::getClient()->resource(ResourceType::TICKET);

        $link = self::getClient()->resource(ResourceType::LINK);
        $link->add($source_ticket, $target_ticket);

        $this->assertNotEmpty($link->getError());
        $this->assertSame('Tickets not valid.', $link->getError());
    }

    public function testRemoveWithoutSourceTicket()
    {
        $link = self::getClient()->resource(ResourceType::LINK);

        $this->expectException(\TypeError::class);

        $link->remove(null, null);
    }

    public function testRemoveWithoutTargetTicket()
    {
        $link = self::getClient()->resource(ResourceType::LINK);

        $this->expectException(\TypeError::class);

        $link->remove(self::$source_ticket, null);
    }

    public function testRemoveWithInvalidLinkType()
    {
        $link = self::getClient()->resource(ResourceType::LINK);
        $link->remove(self::$source_ticket, self::$target_ticket, 'invalid_type');

        $this->assertNotEmpty($link->getError());
        $this->assertSame('Linktype is not supported.', $link->getError());
    }

    public function testRemoveWithInvalidTickets()
    {
        $source_ticket = self::getClient()->resource(ResourceType::TICKET);
        $target_ticket = self::getClient()->resource(ResourceType::TICKET);

        $link = self::getClient()->resource(ResourceType::LINK);
        $link->remove($source_ticket, $target_ticket);

        $this->assertNotEmpty($link->getError());
        $this->assertSame('Tickets not valid.', $link->getError());
    }

    public function testRemove()
    {
        $link = self::getClient()->resource(ResourceType::LINK);
        $link->add(self::$source_ticket, self::$target_ticket, 'normal');

        $this->assertFalse($link->hasError());

        $link = self::getClient()->resource(ResourceType::LINK);
        $result = $link->remove(self::$source_ticket, self::$target_ticket, 'normal');

        $this->assertSame($link, $result);
        $this->assertFalse($link->hasError(), 'Remove error: ' . $link->getError());
    }

    public function testAddAndVerify()
    {
        $link = self::getClient()->resource(ResourceType::LINK);
        $result = $link->add(self::$source_ticket, self::$target_ticket, 'normal');

        $this->assertSame($link, $result);
        $this->assertFalse($link->hasError());

        $link = self::getClient()->resource(ResourceType::LINK);
        $link->get(self::$source_ticket->getID(), 'Ticket');

        $links = $link->getValue('links');
        $this->assertIsArray($links);

        $found_link = false;
        foreach ($links as $linked_ticket) {
            if ($linked_ticket['link_object_value'] == self::$target_ticket->getID()
                && $linked_ticket['link_type'] === 'normal') {
                $found_link = true;
                break;
            }
        }
        $this->assertTrue($found_link, 'Link was not found after add operation');
    }

    public function testRemoveAndVerify()
    {
        $link = self::getClient()->resource(ResourceType::LINK);
        $link->add(self::$source_ticket, self::$target_ticket, 'normal');

        $this->assertFalse($link->hasError());

        $link = self::getClient()->resource(ResourceType::LINK);
        $result = $link->remove(self::$source_ticket, self::$target_ticket, 'normal');

        $this->assertSame($link, $result);
        $this->assertFalse($link->hasError(), 'Remove error: ' . $link->getError());

        $link = self::getClient()->resource(ResourceType::LINK);
        $link->get(self::$source_ticket->getID(), 'Ticket');

        $links = $link->getValue('links');
        $this->assertIsArray($links);

        $found_link = false;
        foreach ($links as $linked_ticket) {
            if ($linked_ticket['link_object_value'] == self::$target_ticket->getID()
                && $linked_ticket['link_type'] === 'normal') {
                $found_link = true;
                break;
            }
        }
        $this->assertFalse($found_link, 'Link was still found after remove operation');
    }
}
