<?php
declare(strict_types=1);

namespace ZammadAPIClient;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use ZammadAPIClient\Client;
use ZammadAPIClient\EnvConfigTrait;
use ZammadAPIClient\HTTPClientInterface;
use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\ResponseInterface;

class ClientTest extends TestCase
{
    use EnvConfigTrait;

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

    public function testSetsFromHeaderWhenOnBehalfOfUserIsSet()
    {
        $client = $this->createClientWithMockHttpClient();
        $client->setOnBehalfOfUser('testuser');
        $client->get('tickets');

        $this->assertArrayHasKey('From', $this->capturedOptions['headers']);
        $this->assertSame('testuser', $this->capturedOptions['headers']['From']);
    }

    public function testDoesNotSetFromHeaderByDefault()
    {
        $client = $this->createClientWithMockHttpClient();
        $client->get('tickets');

        $this->assertArrayNotHasKey('From', $this->capturedOptions['headers']);
    }

    public function testRemovesFromHeaderAfterUnsetOnBehalfOfUser()
    {
        $client = $this->createClientWithMockHttpClient();
        $client->setOnBehalfOfUser('testuser');
        $client->unsetOnBehalfOfUser();
        $client->get('tickets');

        $this->assertArrayNotHasKey('From', $this->capturedOptions['headers']);
    }

    #[Group('integration')]
    public function testFromHeaderAgainstZammad()
    {
        $client = self::createZammadClient();
        if (!$client) {
            $this->markTestSkipped(
                'Zammad environment variables not set. '
                . 'Set ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_URL, '
                . 'ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_USERNAME, '
                . 'ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_PASSWORD.'
            );
        }

        $client->setOnBehalfOfUser(getenv('ZAMMAD_PHP_API_CLIENT_UNIT_TESTS_USERNAME'));
        $response = $client->get('users');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->hasError());

        $client->unsetOnBehalfOfUser();
        $response = $client->get('users');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->hasError());
    }

    private $capturedOptions = [];

    private function createClientWithMockHttpClient()
    {
        $this->capturedOptions = [];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getReasonPhrase')->willReturn('OK');
        $mockResponse->method('getBody')->willReturn('{}');
        $mockResponse->method('getHeaders')->willReturn([]);

        $mockHttpClient = $this->createMock(HTTPClientInterface::class);
        $mockHttpClient
            ->method('request')
            ->will($this->returnCallback(function ($method, $uri, $options) use ($mockResponse) {
                $this->capturedOptions = $options;
                return $mockResponse;
            }));

        return new Client([
            'url'      => 'https://example.com/',
            'username' => 'test',
            'password' => 'test',
        ], $mockHttpClient);
    }
}
