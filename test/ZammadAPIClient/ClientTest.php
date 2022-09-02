<?php

namespace ZammadAPIClient;

use PHPUnit\Framework\TestCase;

use ZammadAPIClient\Client;
use GuzzleHttp\Exception\ConnectException;

class ClientTest extends TestCase
{
    public function testNetworkError()
    {
        // When providing a wrong URL, there must be a proper exception thrown.
        $this->expectException( \GuzzleHttp\Exception\ConnectException::class );

        $client = new Client([
            'url'      => 'https://non.existing.ci/',
            'username' => 'nonexisting',
            'password' => 'nonexisting',
        ]);
        $client->get('/nonexisting');
    }
}
