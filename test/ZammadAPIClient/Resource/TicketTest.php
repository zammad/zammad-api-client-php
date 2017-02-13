<?php

namespace ZammadAPIClient\Resource;

use ZammadAPIClient\ResourceType;

class TicketTest extends AbstractBaseTest
{
    protected $resource_type = ResourceType::TICKET;
    protected $update_field  = 'title';

    public function objectCreateProvider()
    {
        $configs = [
            // Minimum valid object data.
            [
                'values' => [
                    'group_id'    => 1,
                    'priority_id' => 1,
                    'state_id'    => 1,
                    'title'       => 'Unit test ticket 1 ' . $this->getUniqueID(),
                    'customer_id' => 1,
                    'article'     => [
                        'subject' => 'Unit test article 1 ' . $this->getUniqueID(),
                        'body'    => 'Unit test article 1... ' . $this->getUniqueID(),
                    ],
                ],
                'expected_success' => true,
            ],
            // Another object with valid data.
            [
                'values' => [
                    'group_id'    => 1,
                    'priority_id' => 1,
                    'state_id'    => 1,
                    'title'       => 'Unit test ticket 2 ' . $this->getUniqueID(),
                    'customer_id' => 1,
                    'article'     => [
                        'subject' => 'Unit test article 2 ' . $this->getUniqueID(),
                        'body'    => 'Unit test article 2... ' . $this->getUniqueID(),
                    ],
                ],
                'expected_success' => true,
            ],
            // Missing required field 'group_id'.
            [
                'values' => [
                    // 'group_id'    => 1,
                    'priority_id' => 1,
                    'state_id'    => 1,
                    'title'       => 'Unit test ticket 3 ' . $this->getUniqueID(),
                    'customer_id' => 1,
                    'article'     => [
                        'subject' => 'Unit test article 3 ' . $this->getUniqueID(),
                        'body'    => 'Unit test article 3... ' . $this->getUniqueID(),
                    ],
                ],
                'expected_success' => false,
            ],
            // Missing article data.
            [
                'values' => [
                    'group_id'    => 1,
                    'priority_id' => 1,
                    'state_id'    => 1,
                    'title'       => 'Unit test ticket 6 ' . $this->getUniqueID(),
                    'customer_id' => 1,
                    // 'article'     => [
                    //     'subject' => 'Unit test article 6' . $this->getUniqueID(),
                    //     'body'    => 'Unit test article 6... ' . $this->getUniqueID(),
                    // ],
                ],
                'expected_success' => false,
            ],
        ];

        return $configs;
    }

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

            // Ignore certain fields from test config because
            // they cannot be compared directly.
            if ( $field == 'article' ) {
                continue;
            }

            // Compare via value from getValue()
            $this->assertEquals(
                $expected_value,
                $object->getValue($field),
                "Value of object must match expected value (field $field)."
            );
        }

        // Compare article data.
        $articles = $object->getTicketArticles();
        $this->assertInternalType(
            'array',
            $articles,
            'Articles of ticket object must be returned as array.'
        );
        $this->assertCount(
            1,
            $articles,
            'Ticket object must have exactly one article.'
        );

        $article = array_shift($articles);
        foreach ( $values['article'] as $field => $expected_value ) {

            // Compare via value from getValue()
            $this->assertEquals(
                $expected_value,
                $article->getValue($field),
                "Value of article must match expected value (field $field)."
            );
        }
    }

    /**
     * @depends testCreate
     */
    public function testGet()
    {
        return parent::testGet();
    }

    /**
     * @expectedException \ZammadAPIClient\Exception\AlreadyFetchedObjectException
     * @depends testCreate
     */
    public function testGetOnFilledObjects()
    {
        return parent::testGetOnFilledObjects();
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        return parent::testUpdate();
    }

    /**
     * @depends testCreate
     */
    public function testAll()
    {
        return parent::testAll();
    }

    /**
     * @depends testCreate
     */
    public function testAllPagination()
    {
        return parent::testAllPagination();
    }

    /**
     * @expectedException \ZammadAPIClient\Exception\AlreadyFetchedObjectException
     * @depends testCreate
     */
    public function testAllOnFilledObjects()
    {
        return parent::testAllOnFilledObjects();
    }

    /**
     * @depends testCreate
     */
    public function testSearch()
    {
        return parent::testSearch();
    }

    /**
     * @depends testCreate
     */
    public function testSearchPagination()
    {
        return parent::testSearchPagination();
    }

    /**
     * @depends testCreate
     */
    public function testDelete()
    {
        return parent::testDelete();
    }
}
