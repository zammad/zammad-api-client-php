<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit\Core;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use ZammadAPIClient\Core\Transport\RequestHandler;
use ZammadAPIClient\Core\Repository\Resource;
use ZammadAPIClient\Endpoints\Tickets\TicketDTO;

#[Group('unit')]
final class ResourceTest extends TestCase
{
    use \ZammadAPIClient\Tests\Unit\Core\Traits\CreatesRequestHandler;

    private RequestHandler $handler;

    /** @var object{lastRequest: ?RequestInterface, lastBody: ?string, response: ResponseInterface} */
    private object $httpClient;

    protected function setUp(): void
    {
        $this->setUpRequestHandler();

        $this->httpClient = new class implements ClientInterface {
            public ?RequestInterface $lastRequest = null;
            public ?string $lastBody = null;
            public ResponseInterface $response;

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->lastRequest = $request;
                $this->lastBody = (string) $request->getBody();

                return $this->response;
            }
        };

        $this->handler = $this->createHandler($this->httpClient);
    }

    public function testGetReturnsDtoProperty(): void
    {
        $dto = TicketDTO::fromArray(['id' => 42, 'title' => 'Test ticket']);
        $resource = new Resource($dto, $this->handler, 'tickets');

        self::assertSame('Test ticket', $resource->title);
        self::assertSame(42, $resource->id());
    }

    public function testSetTracksChangesAndSaveSendsPut(): void
    {
        $this->httpClient->response = new Response(200, [], (string) json_encode([
            'id' => 42, 'title' => 'Updated', 'group_id' => 1,
        ]));

        $dto = TicketDTO::fromArray(['id' => 42, 'title' => 'Original', 'group_id' => 1]);
        $resource = new Resource($dto, $this->handler, 'tickets');

        $resource->title = 'Updated';
        $resource->save();

        self::assertNotNull($this->httpClient->lastRequest);
        self::assertSame('PUT', $this->httpClient->lastRequest->getMethod());
        self::assertStringContainsString('/tickets/42', (string) $this->httpClient->lastRequest->getUri());
    }

    public function testSaveSendsOnlyChangedFields(): void
    {
        $this->httpClient->response = new Response(200, [], (string) json_encode([
            'id' => 42, 'title' => 'Updated', 'group_id' => 1,
        ]));

        $dto = TicketDTO::fromArray(['id' => 42, 'title' => 'Original', 'group_id' => 1]);
        $resource = new Resource($dto, $this->handler, 'tickets');

        $resource->title = 'Updated';
        $resource->save();

        $body = json_decode($this->httpClient->lastBody ?? '', true);

        self::assertArrayHasKey('title', $body);
        self::assertArrayNotHasKey('group_id', $body);
    }

    public function testDestroySendsDelete(): void
    {
        $this->httpClient->response = new Response(200, [], '{}');

        $dto = TicketDTO::fromArray(['id' => 42, 'title' => 'Test']);
        $resource = new Resource($dto, $this->handler, 'tickets');

        $resource->destroy();

        self::assertNotNull($this->httpClient->lastRequest);
        self::assertSame('DELETE', $this->httpClient->lastRequest->getMethod());
        self::assertStringContainsString('/tickets/42', (string) $this->httpClient->lastRequest->getUri());
    }

    public function testSaveSendsPostForNewRecord(): void
    {
        $this->httpClient->response = new Response(200, [], (string) json_encode([
            'id' => 1, 'title' => 'Created', 'group_id' => 1,
        ]));

        $dto = TicketDTO::fromArray(['title' => 'New ticket', 'group_id' => 1]);
        $resource = new Resource($dto, $this->handler, 'tickets');

        $resource->title = 'New ticket';
        $resource->save();

        self::assertSame('POST', $this->httpClient->lastRequest->getMethod());
        self::assertStringContainsString('/tickets', (string) $this->httpClient->lastRequest->getUri());
    }

    public function testSavePreservesArticleInResponse(): void
    {
        $this->httpClient->response = new Response(200, [], (string) json_encode([
            'id' => 1, 'title' => 'Test', 'article' => ['body' => 'Hello'],
        ]));

        $dto = TicketDTO::fromArray(['title' => 'Test', 'group_id' => 1]);
        $resource = new Resource($dto, $this->handler, 'tickets');

        $resource->save();

        self::assertIsArray($resource->article);
        self::assertSame('Hello', $resource->article['body']);
    }

    public function testSaveDoesNothingWhenNoChanges(): void
    {
        $this->httpClient->response = new Response(200, [], '{}');

        $dto = TicketDTO::fromArray(['id' => 1, 'title' => 'Test']);
        $resource = new Resource($dto, $this->handler, 'tickets');

        $resource->save();

        self::assertNull($this->httpClient->lastRequest);
    }

    public function testChangesClearedAfterSave(): void
    {
        $this->httpClient->response = new Response(200, [], (string) json_encode([
            'id' => 1, 'title' => 'Changed', 'group_id' => 1,
        ]));

        $dto = TicketDTO::fromArray(['id' => 1, 'title' => 'Original', 'group_id' => 1]);
        $resource = new Resource($dto, $this->handler, 'tickets');

        $resource->title = 'Changed';
        $resource->save();

        self::assertFalse($resource->changed());
        self::assertSame([], $resource->changes());
    }
}
