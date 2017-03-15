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
            // Article with attachments.
            [
                'values' => [
                    'subject'   => 'Unit test ticket article 2' . $this->getUniqueID(),
                    'body'      => 'Unit test ticket article 2...' . $this->getUniqueID(),
                    'ticket_id' => 1,
                    'attachments' => [
                        [
                            'filename'  => 'test_file.jpg',
                            'data'      => $this->getTestFileContentBase64('test_file.jpg'),
                            'mime-type' => 'image/jpg',
                        ],
                        [
                            'filename'  => 'test_file.txt',
                            'data'      => $this->getTestFileContentBase64('test_file.txt'),
                            'mime-type' => 'text/plain',
                        ],
                    ],
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

            // Compare content of attachments
            if ( $field == 'attachments' ) {

                $attachments = $object->getValue($field);
                foreach ( $expected_value as $expected_attachment ) {
                    $filename         = $expected_attachment['filename'];
                    $expected_content = $this->getTestFileContent($filename);
                    $attachment_index = array_search( $filename, array_column( $attachments, 'filename' ) );

                    $this->assertTrue(
                        $attachment_index !== false,
                        "File $filename must be found in article attachments."
                    );

                    $attachment = $attachments[$attachment_index];

                    $this->assertEquals(
                        strlen($expected_content),
                        $attachment['size'],
                        "Size of file $filename must match expected one."
                    );

                    // Fetch attachment content
                    $content = $object->getAttachmentContent( $attachment['id'] );
                    $this->assertEquals(
                        $expected_content,
                        $content,
                        "Content of file $filename must match expected one."
                    );
                }

                continue;
            }

            // Compare via value from getValue()
            $this->assertEquals(
                $expected_value,
                $object->getValue($field),
                "Value of object must match expected value (field $field)."
            );
        }
    }
}
