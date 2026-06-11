<?php
declare(strict_types=1);

namespace ZammadAPIClient;

use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Resource\AbstractResource;

class GetIDTest extends TestCase
{
    private static $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = new Client([
            'url'      => 'http://localhost:3000/',
            'username' => 'test@example.com',
            'password' => 'test',
        ]);
    }

    public static function getClient()
    {
        return self::$client;
    }

    public function testGetIDBeforeSave()
    {
        $object = self::getClient()->resource( ResourceType::TICKET );

        $this->assertNull(
            $object->getID(),
            'getID() must return null for unsaved object.'
        );
    }

    public function testGetIDReturnsString()
    {
        $object = self::getClient()->resource( ResourceType::TICKET );
        $object->setValue('id', 123);

        $id = $object->getID();

        $this->assertIsString(
            $id,
            'getID() must return a string.'
        );

        $this->assertSame(
            '123',
            $id,
            'getID() must cast integer to string.'
        );
    }

    public function testGetIDReturnsNullWhenIdNotSet()
    {
        $object = self::getClient()->resource( ResourceType::TICKET );
        $object->setValue('title', 'test');

        $this->assertNull(
            $object->getID(),
            'getID() must return null when id is not set.'
        );
    }
}
