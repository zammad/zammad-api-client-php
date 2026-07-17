<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core;

use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ZammadAPIClient\Core\RequestHandler;
use ZammadAPIClient\Exceptions\AuthenticationException;
use ZammadAPIClient\Exceptions\ForbiddenException;
use ZammadAPIClient\Exceptions\NetworkException;
use ZammadAPIClient\Exceptions\NotFoundException;
use ZammadAPIClient\Exceptions\RateLimitException;
use ZammadAPIClient\Exceptions\ServerErrorException;
use ZammadAPIClient\Exceptions\ValidationException;

#[Group('unit')]
final class RequestHandlerTest extends TestCase
{
    use \ZammadAPIClient\Tests\Unit\Core\Traits\CreatesRequestHandler;

    /** @var object{lastRequest: ?RequestInterface, response: ResponseInterface} */
    private object $httpClient;

    private RequestHandler $handler;

    protected function setUp(): void
    {
        $this->setUpRequestHandler();

        $this->httpClient = new class implements ClientInterface {
            public ?RequestInterface $lastRequest = null;
            public ResponseInterface $response;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->lastRequest = $request;

                return $this->response;
            }
        };

        $this->handler = $this->createHandler($this->httpClient);
    }

    public function testGetDecodesJsonBody(): void
    {
        $this->httpClient->response = new Response(200, [], (string) json_encode(['id' => 7, 'title' => 'x']));

        $data = $this->handler->get('tickets/7');

        self::assertSame(['id' => 7, 'title' => 'x'], $data);
    }

    public function testNotFoundMapsToTypedException(): void
    {
        $this->httpClient->response = new Response(404, [], '<html>nope</html>');

        $this->expectException(NotFoundException::class);
        $this->handler->get('tickets/999');
    }

    public function testValidationExceptionCarriesDetails(): void
    {
        $this->httpClient->response = new Response(
            422,
            [],
            (string) json_encode(['error' => 'bad', 'details' => ['title' => 'required']]),
        );

        try {
            $this->handler->post('tickets', ['x' => 1]);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertSame('bad', $e->getMessage());
            self::assertSame(['title' => 'required'], $e->errors);
        }
    }

    public function testValidationExceptionParsesHtmlErrorPage(): void
    {
        $html = '<!DOCTYPE html><html class="dark"><title>422: Unprocessable Content</title></html>';
        $this->httpClient->response = new Response(422, [], $html);

        try {
            $this->handler->delete('users/1');
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertStringNotContainsStringIgnoringCase('<!doctype', $e->getMessage());
            self::assertStringNotContainsStringIgnoringCase('<html', $e->getMessage());
        }
    }

    public function testRateLimitReadsRetryAfter(): void
    {
        $this->httpClient->response = new Response(429, ['Retry-After' => '30'], '');

        try {
            $this->handler->get('tickets');
            self::fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            self::assertSame(30, $e->retryAfterSeconds);
        }
    }

    public function testServerErrorMapsToTypedException(): void
    {
        $this->httpClient->response = new Response(503, [], 'upstream down');

        $this->expectException(ServerErrorException::class);
        $this->handler->get('tickets');
    }

    public function testUnauthorizedMapsToTypedException(): void
    {
        $this->httpClient->response = new Response(401, [], '');

        $this->expectException(AuthenticationException::class);
        $this->handler->get('tickets');
    }

    public function testForbiddenMapsToTypedException(): void
    {
        $this->httpClient->response = new Response(403, [], '');

        $this->expectException(ForbiddenException::class);
        $this->handler->get('tickets');
    }

    public function testGetRawReturnsUndecodedBody(): void
    {
        $binary = "PNG\x00\x01binary-not-json";
        $this->httpClient->response = new Response(200, [], $binary);

        self::assertSame($binary, $this->handler->getRaw('ticket_attachment/1/2/3'));
    }

    public function testOnBehalfOfHeaderIsApplied(): void
    {
        $this->httpClient->response = new Response(200, [], '{}');
        $this->handler->setOnBehalfOfUser(7);

        $this->handler->get('tickets');

        self::assertNotNull($this->httpClient->lastRequest);
        self::assertSame('7', $this->httpClient->lastRequest->getHeaderLine('From'));
    }

    public function testOnBehalfOfWithStringLogin(): void
    {
        $this->httpClient->response = new Response(200, [], '{}');
        $this->handler->setOnBehalfOfUser('agent@example.com');

        $this->handler->get('tickets');

        self::assertNotNull($this->httpClient->lastRequest);
        self::assertSame('agent@example.com', $this->httpClient->lastRequest->getHeaderLine('From'));
    }

    public function testNonJsonBodyOn200ThrowsNetworkException(): void
    {
        $this->httpClient->response = new Response(200, [], '<html>proxy error</html>');

        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('Failed to decode JSON response');

        $this->handler->get('tickets');
    }

    public function testGetLastResponseReturnsNullInitially(): void
    {
        self::assertNull($this->handler->getLastResponse());
    }

    public function testGetLastResponseReturnsLastResponseAfterRequest(): void
    {
        $this->httpClient->response = new Response(200, [], '{}');
        $this->handler->get('tickets');

        self::assertNotNull($this->handler->getLastResponse());
    }

    public function testGetOnBehalfOfUserReturnsSetValue(): void
    {
        self::assertNull($this->handler->getOnBehalfOfUser());

        $this->handler->setOnBehalfOfUser(7);
        self::assertSame(7, $this->handler->getOnBehalfOfUser());

        $this->handler->setOnBehalfOfUser('user@example.com');
        self::assertSame('user@example.com', $this->handler->getOnBehalfOfUser());

        $this->handler->setOnBehalfOfUser(null);
        self::assertNull($this->handler->getOnBehalfOfUser());
    }

    public function testGetRawWithQueryParamsAppendsToUri(): void
    {
        $this->httpClient->response = new Response(200, [], 'binary');

        $result = $this->handler->getRaw('ticket_attachment/1/2/3', ['disposition' => 'inline']);

        self::assertSame('binary', $result);
        self::assertNotNull($this->httpClient->lastRequest);
        self::assertStringContainsString('disposition=inline', (string) $this->httpClient->lastRequest->getUri());
    }

    public function testConstructorRejectsFactoryWithoutStreamFactory(): void
    {
        $factory = Mockery::mock(RequestFactoryInterface::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement both RequestFactoryInterface and StreamFactoryInterface');

        new RequestHandler(
            $this->httpClient,
            $factory,
            'https://zammad.example/api/v1',
        );
    }

    public function testDispatchCatchesClientException(): void
    {
        $httpClient = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                throw new class extends \RuntimeException implements ClientExceptionInterface {};
            }
        };

        $handler = $this->createHandler($httpClient);

        $this->expectException(NetworkException::class);
        $handler->get('tickets');
    }

    public function testValidationMessageFallbackForPlainText(): void
    {
        $this->httpClient->response = new Response(422, [], 'title is required');

        try {
            $this->handler->post('tickets', ['x' => 1]);
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            self::assertStringContainsString('title is required', $e->getMessage());
            self::assertStringNotContainsStringIgnoringCase('HTML', $e->getMessage());
        }
    }

    public function testDeleteReturnsEmptyArrayOnEmptyBody(): void
    {
        $this->httpClient->response = new Response(200, [], '');

        $result = $this->handler->delete('users/1');

        self::assertSame([], $result);
    }
}
