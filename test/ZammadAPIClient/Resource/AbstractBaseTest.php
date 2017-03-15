<?php

namespace ZammadAPIClient\Resource;

use PHPUnit\Framework\TestCase;

use ZammadAPIClient\Client;

abstract class AbstractBaseTest extends TestCase
{
    private static $client;
    protected $resource_type;
    protected static $created_objects = [];
    protected $update_field;
    protected static $unique_id;

    public static function setUpBeforeClass()
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

    public static function getClient()
    {
        return self::$client;
    }

    private static function setUniqueID()
    {
        self::$unique_id = uniqid( '', true );
    }

    protected static function getUniqueID()
    {
        if ( empty( self::$unique_id ) ) {
            self::setUniqueID();
        }

        return self::$unique_id;
    }

    protected function getTestFileContent($filename)
    {
        return file_get_contents( __DIR__ . "/$filename" );
    }

    protected function getTestFileContentBase64($filename)
    {
        return base64_encode( $this->getTestFileContent($filename) );
    }

    abstract public function objectCreateProvider();

    /**
     * @dataProvider objectCreateProvider
     */
    public function testCreate( $values, $expected_success )
    {
        $object = self::getClient()->resource( $this->resource_type );
        $this->assertInstanceOf(
            $this->resource_type,
            $object
        );

        self::$created_objects[] = $object;

        $this->assertFalse(
            $object->isDirty(),
            'Dirty flag of object must not be set after object creation.'
        );

        $object->setValues($values);

        $this->assertEquals(
            $values,
            $object->getUnsavedValues(),
            'Unsaved values must match set values.'
        );

        $this->assertTrue(
            $object->isDirty(),
            'Dirty flag of object must be set after setting values.'
        );

        $saved_object = $object->save();
        $this->assertSame(
            $object,
            $saved_object,
            'Saving an object must return the same object again.'
        );

        if ($expected_success) {
            $this->assertFalse(
                $object->hasError(),
                'Error must not be set after saving.'
            );

            $this->assertEmpty(
                $object->getError(),
                'Error must be empty after saving.'
            );

            $this->assertFalse(
                $object->isDirty(),
                'Dirty flag of object must not be set after saving.'
            );
        }
        else {
            $this->assertTrue(
                $object->hasError(),
                'Error must be set after failed save.'
            );

            $this->assertNotEmpty(
                $object->getError(),
                'Error must not be empty after failed save.'
            );

            $this->assertTrue(
                $object->isDirty(),
                'Dirty flag of object must be set after failed save.'
            );
        }

        if ( $object->hasError() ) {
            return;
        }

        // Compare values of object fields with expected ones.
        foreach( $values as $field => $expected_value ) {

            // Compare via value from getValue()
            $this->assertEquals(
                $expected_value,
                $object->getValue($field),
                "Value of object must match expected value (field $field)."
            );
        }
    }

    /**
     * @depends testCreate
     */
    public function testGet()
    {
        // Compare data of created objects with fetched object data.
        foreach ( self::$created_objects as $created_object ) {
            $created_object_id = $created_object->getID();
            if ( empty( $created_object_id) ) {
                continue;
            }

            $object = self::getClient()->resource( $this->resource_type )->get($created_object_id);

            $this->assertInstanceOf(
                $this->resource_type,
                $object
            );

            $this->assertFalse(
                $object->hasError(),
                'Error must not be set.'
            );

            $this->assertEquals(
                $created_object->getValues(),
                $object->getValues(),
                'Object values must match expected ones.'
            );
        }
    }

    /**
     * @expectedException \ZammadAPIClient\Exception\AlreadyFetchedObjectException
     * @depends testCreate
     */
    public function testGetOnFilledObjects()
    {
        foreach ( self::$created_objects as $created_object ) {
            $created_object_id = $created_object->getID();
            if ( empty( $created_object_id) ) {
                continue;
            }

            $created_object->get(2);
        }
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        foreach ( self::$created_objects as $created_object ) {
            $created_object_id = $created_object->getID();
            if ( empty( $created_object_id ) ) {
                continue;
            }

            // Change a value.
            $changed_value = $created_object->getValue( $this->update_field ) . 'CHANGED';
            $created_object->setValue( $this->update_field, $changed_value );
            $saved_object = $created_object->save();

            $this->assertFalse(
                $created_object->hasError(),
                'Error must not be set after update of object.'
            );

            $this->assertInstanceOf(
                $this->resource_type,
                $saved_object
            );

            $this->assertSame(
                $created_object,
                $saved_object,
                'Saving an object must return the same object again.'
            );

            // Compare changed value.
            $this->assertEquals(
                $changed_value,
                $created_object->getValue( $this->update_field ),
                'Changed value of object must match expected one.'
            );

            // Fetch object data with a fresh object to check again if value has been changed.
            $fetched_object = self::getClient()->resource( $this->resource_type )->get($created_object_id);
            $this->assertEquals(
                $changed_value,
                $fetched_object->getValue( $this->update_field ),
                'Value of fetched object must match expected one.'
            );
        }
    }

    /**
     * @depends testCreate
     */
    public function testAll()
    {
        if ( !self::getClient()->resource( $this->resource_type )->can('all') ) {
            return;
        }

        // Note: all() will be tested by checking if the created objects will be returned.
        // Note 2: Since created objects will be deleted, the server side limit for the number of returned objects
        //        can be ignored.
        $objects = self::getClient()->resource( $this->resource_type )->all();
        foreach ( self::$created_objects as $created_object ) {
            $created_object_id = $created_object->getID();
            if ( empty( $created_object_id ) ) {
                continue;
            }

            $created_object_found = false;
            foreach ( $objects as $object ) {
                if ( $object->getID() != $created_object_id ) {
                    continue;
                }

                $created_object_found = true;
                break;
            }

            $this->assertTrue(
                $created_object_found,
                "Object with ID $created_object_id must be returned by all()."
            );
        }
    }

    /**
     * @depends testCreate
     */
    public function testAllPagination()
    {
        if ( !self::getClient()->resource( $this->resource_type )->can('all') ) {
            return;
        }

        $all_objects = self::getClient()->resource( $this->resource_type )->all();
        $page_count = 0;
        foreach ( $all_objects as $object ) {
            $page_count++;

            // Fetch objects one per page
            $objects = self::getClient()->resource( $this->resource_type )->all( $page_count, 1 );

            $this->assertCount(
                1,
                $objects,
                'Number of objects returned must be 1.'
            );

            $this->assertEquals(
                $object->getID(),
                $objects[0]->getID(),
                'ID of returned object must match expected one.'
            );
        }
    }

    /**
     * @expectedException \ZammadAPIClient\Exception\AlreadyFetchedObjectException
     * @depends testCreate
     */
    public function testAllOnFilledObjects()
    {
        foreach ( self::$created_objects as $created_object ) {
            $created_object_id = $created_object->getID();
            if ( empty( $created_object_id) ) {
                continue;
            }

            $created_object->all();
        }
    }

    /**
     * @depends testCreate
     */
    public function testSearch()
    {
        if ( !self::getClient()->resource( $this->resource_type )->can('search') ) {
            return;
        }

        $objects = self::getClient()->resource( $this->resource_type )->search( $this->getUniqueID() );
        $this->assertCount(
            2,
            $objects,
            'Number of found objects must be 2.'
        );
    }

    /**
     * @depends testCreate
     */
    public function testSearchPagination()
    {
        if ( !self::getClient()->resource( $this->resource_type )->can('search') ) {
            return;
        }

        $objects = self::getClient()->resource( $this->resource_type )->search( $this->getUniqueID(), 1, 1 );
        $this->assertCount(
            1,
            $objects,
            'Number of found objects must be 1.'
        );
    }

    /**
     * @depends testCreate
     */
    public function testDelete()
    {
        foreach ( self::$created_objects as $created_object ) {
            $created_object_id = $created_object->getID();
            if ( empty( $created_object_id ) ) {
                continue;
            }

            $deleted_object = $created_object->delete();

            $this->assertInstanceOf(
                $this->resource_type,
                $deleted_object
            );

            $this->assertSame(
                $created_object,
                $deleted_object,
                'Deleting an object must return the same object again.'
            );

            // Workaround for objects that cannot be deleted because they have references to
            // other objects (Zammad internal).
            if (
                $deleted_object->hasError()
                && $deleted_object->getError() == "Can't delete, object has references."
            ) {
                continue;
            }

            $this->assertFalse(
                $deleted_object->hasError(),
                'Error must not be set after deleting object.'
            );

            $fetched_object = self::getClient()->resource( $this->resource_type )->get($created_object_id);

            $this->assertInstanceOf(
                $this->resource_type,
                $fetched_object
            );

            $this->assertTrue(
                $fetched_object->hasError(),
                'Error must be set after trying to fetch deleted object.'
            );
            $this->assertEmpty(
                $fetched_object->getValues(),
                'Values must be empty after fetching deleted object.'
            );
        }
    }
}
