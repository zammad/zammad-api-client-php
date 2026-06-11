<?php
declare(strict_types=1);

namespace ZammadAPIClient\Resource;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ZammadAPIClient\Client;
use ZammadAPIClient\ResourceType;

#[Group('integration')]
class TagTest extends TestCase
{
    private static $client;
    private static $ticket;

    protected $resource_type = ResourceType::TAG;

    public static function setUpBeforeClass(): void
    {
        $client_config = [];

        $env_keys = [
            'url'      => 'ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL',
            'username' => 'ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_USERNAME',
            'password' => 'ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_PASSWORD',
        ];
        foreach ( $env_keys as $config_key => $env_key ) {
            $value = getenv($env_key);
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
        self::createTicket();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        self::deleteTicket();
    }

    public static function getClient()
    {
        return self::$client;
    }

    public function testAdd()
    {
        $tag          = self::getUniqueID();
        $object       = self::getClient()->resource( $this->resource_type );
        $saved_object = $object->add( self::$ticket->getID(), $tag, 'Ticket' );

        $this->assertSame(
            $object,
            $saved_object,
            'Saving an object must return the same object again.'
        );

        $this->assertFalse(
            $object->hasError(),
            'Error must not be set after saving.'
        );

        $this->assertEmpty(
            $object->getError(),
            'Error must be empty after saving.'
        );
    }

    public function testGet()
    {
        $tag = self::getUniqueID();

        $object = self::getClient()->resource( $this->resource_type )
            ->add( self::$ticket->getID(), $tag, 'Ticket' )
            ->get( self::$ticket->getID() );

        $this->assertInstanceOf(
            $this->resource_type,
            $object
        );

        $this->assertFalse(
            $object->hasError(),
            'Error must not be set.'
        );

        $this->assertEquals([$tag], $object->getValue('tags'));
    }

    public function testSearch()
    {
        $tag = self::getUniqueID();

        self::getClient()->resource( $this->resource_type )->add( self::$ticket->getID(), $tag, 'Ticket' );

        $objects = self::getClient()->resource( $this->resource_type )->search($tag);

        $this->assertCount(
            1,
            $objects,
            'Number of found objects must be 1.'
        );
    }

    public function testRemove()
    {
        $tag = self::getUniqueID();

        $deleted_object = self::getClient()
            ->resource( $this->resource_type )
            ->add( self::$ticket->getID(), $tag, 'Ticket' )
            ->remove( self::$ticket->getID(), $tag, 'Ticket' );

        $this->assertInstanceOf(
            $this->resource_type,
            $deleted_object
        );

        $object = self::getClient()
            ->resource( $this->resource_type )
            ->get( self::$ticket->getID(), 'Ticket' );

        $this->assertCount(
            0,
            $object->getValue('tags'),
            'Number of found objects must be 0.'
        );
    }

    public function testAdminAll()
    {
        $tags = self::getClient()->resource( $this->resource_type )->all();

        $this->assertIsArray(
            $tags,
            'all() must return an array.'
        );

        if ( count($tags) > 0 ) {
            $tag = $tags[0];
            $this->assertInstanceOf(
                $this->resource_type,
                $tag,
                'Elements must be Tag instances.'
            );
            $this->assertNotNull(
                $tag->getValue('id'),
                'Tag must have an id field.'
            );
            $this->assertNotNull(
                $tag->getValue('name'),
                'Tag must have a name field.'
            );
        }
    }

    public function testAdminCreate()
    {
        $tag_name = self::getUniqueID();

        $tag = self::getClient()->resource( $this->resource_type );
        $tag->setValue('name', $tag_name);
        $saved_tag = $tag->save();

        $this->assertFalse(
            $saved_tag->hasError(),
            'Error must not be set after creating tag.'
        );

        $tags = self::getClient()->resource( $this->resource_type )->all();
        $found = false;
        foreach ( $tags as $t ) {
            if ( $t->getValue('name') === $tag_name ) {
                $found = true;
                break;
            }
        }

        $this->assertTrue(
            $found,
            'Created tag must appear in all().'
        );
    }

    public function testAdminUpdate()
    {
        $tag_name = self::getUniqueID();

        $tag = self::getClient()->resource( $this->resource_type );
        $tag->setValue('name', $tag_name);
        $tag->save();

        $this->assertFalse(
            $tag->hasError(),
            'Error must not be set after creating tag for update.'
        );

        $tags = self::getClient()->resource( $this->resource_type )->all();
        $found_tag = null;
        foreach ( $tags as $t ) {
            if ( $t->getValue('name') === $tag_name ) {
                $found_tag = $t;
                break;
            }
        }

        $this->assertNotNull(
            $found_tag,
            'Created tag must be found in all().'
        );

        $new_name = $tag_name . '_updated';
        $found_tag->setValue('name', $new_name);
        $found_tag->save();

        $this->assertFalse(
            $found_tag->hasError(),
            'Error must not be set after updating tag.'
        );

        $tags = self::getClient()->resource( $this->resource_type )->all();
        $found_updated = false;
        $found_old = false;
        foreach ( $tags as $t ) {
            if ( $t->getValue('name') === $new_name ) {
                $found_updated = true;
            }
            if ( $t->getValue('name') === $tag_name ) {
                $found_old = true;
            }
        }

        $this->assertTrue(
            $found_updated,
            'Updated tag name must appear in all().'
        );
        $this->assertFalse(
            $found_old,
            'Old tag name must not appear in all().'
        );
    }

    public function testAdminDelete()
    {
        $tag_name = self::getUniqueID();

        $tag = self::getClient()->resource( $this->resource_type );
        $tag->setValue('name', $tag_name);
        $tag->save();

        $this->assertFalse(
            $tag->hasError(),
            'Error must not be set after creating tag for delete.'
        );

        $tags = self::getClient()->resource( $this->resource_type )->all();
        $found_tag = null;
        foreach ( $tags as $t ) {
            if ( $t->getValue('name') === $tag_name ) {
                $found_tag = $t;
                break;
            }
        }

        $this->assertNotNull(
            $found_tag,
            'Created tag must be found in all() before delete.'
        );

        $found_tag->delete();

        $this->assertFalse(
            $found_tag->hasError(),
            'Error must not be set after deleting tag.'
        );

        $tags = self::getClient()->resource( $this->resource_type )->all();
        $found = false;
        foreach ( $tags as $t ) {
            if ( $t->getValue('name') === $tag_name ) {
                $found = true;
                break;
            }
        }

        $this->assertFalse(
            $found,
            'Deleted tag must not appear in all().'
        );
    }

    private static function createTicket()
    {
        self::$ticket = self::getClient()->resource( ResourceType::TICKET );
        self::$ticket->setValues([
            'group_id'    => 1,
            'priority_id' => 1,
            'state_id'    => 1,
            'title'       => 'Unit test ticket 1 ' . self::getUniqueID(),
            'customer_id' => 1,
            'article'     => [
                'subject' => 'Unit test article 1 ' . self::getUniqueID(),
                'body'    => 'Unit test article 1... ' . self::getUniqueID(),
            ],
        ]);
        self::$ticket->save();
    }

    private static function deleteTicket()
    {
        self::$ticket->delete();
    }

    protected static function getUniqueID()
    {
        return uniqid( '', true );
    }
}
