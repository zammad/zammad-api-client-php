<?php

namespace ZammadAPIClient\Client;

use PHPUnit\Framework\TestCase;

use ZammadAPIClient\Client\Response;

class ResponseTest extends TestCase
{
    public function responseTestConfigs()
    {
        $configs = [
            [
                'data' => [
                    'status_code'   => 200,
                    'reason_phrase' => 'OK',
                    'body'          => json_encode( [ 'some_value' => 23, ] ),
                    'headers'       => [
                        'Content-Type' => [
                            'application/json; charset=UTF-8',
                        ],
                    ],
                ],
                'expected_results' => [
                    'getStatusCode'    => 200,
                    'getReasonPhrase'  => 'OK',
                    'getStatusMessage' => '200 - OK',
                    'getBody'          => json_encode( [ 'some_value' => 23, ] ),
                    'getHeaders'       => [
                        'Content-Type' => [
                            'application/json; charset=UTF-8',
                        ],
                    ],
                    'getData'          => [ 'some_value' => 23, ],
                    'getError'         => null,
                    'hasError'         => false,
                ],
            ],
            [
                'data' => [
                    'status_code'   => 200,
                    'reason_phrase' => 'OK',
                    'body'          => json_encode( [ 'error' => 'An error occured.', ] ),
                    'headers'       => [
                        'Content-Type' => [
                            'application/json; charset=UTF-8',
                        ],
                    ],
                ],
                'expected_results' => [
                    'getStatusCode'    => 200,
                    'getReasonPhrase'  => 'OK',
                    'getStatusMessage' => '200 - OK',
                    'getBody'          => json_encode( [ 'error' => 'An error occured.', ] ),
                    'getHeaders'       => [
                        'Content-Type' => [
                            'application/json; charset=UTF-8',
                        ],
                    ],
                    'getData'          => [ 'error' => 'An error occured.', ],
                    'getError'         => 'An error occured.',
                    'hasError'         => true,
                ],
            ],
        ];

        return $configs;
    }

    /**
     * @dataProvider responseTestConfigs
     */
    public function testResponse( $data, $expected_results )
    {
        $response = new Response(
            $data['status_code'],
            $data['reason_phrase'],
            $data['body'],
            $data['headers']
        );

        $this->assertInstanceOf(
            '\\ZammadAPIClient\\Client\\Response',
            $response
        );

        foreach ( $expected_results as $method => $expected_result ) {
            $result = $response->$method();
            $this->assertEquals(
                $expected_result,
                $result,
                $method . '()'
            );
        }
    }
}
