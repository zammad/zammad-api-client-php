<?php

declare(strict_types=1);

namespace ZammadAPIClient\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\Group;
use Psr\Http\Message\ResponseInterface;
use ZammadAPIClient\Core\Contracts\RequestHandlerInterface;
use ZammadAPIClient\Core\Transport\ImpersonationHandler;

#[Group('unit')]
final class ImpersonationHandlerTest extends MockeryTestCase
{
    private RequestHandlerInterface $inner;
    private ImpersonationHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inner = Mockery::mock(RequestHandlerInterface::class);
        $this->handler = new ImpersonationHandler($this->inner, 1);
    }

    public function testRequestInjectsFromHeader(): void
    {
        $this->inner->expects('request')
            ->once()
            ->with(
                'GET',
                '/api/v1/tickets',
                Mockery::on(function (array $options) {
                    return ($options['headers']['From'] ?? null) === '1';
                }),
            )
            ->andReturn([]);

        $this->handler->request('GET', '/api/v1/tickets');
    }

    public function testGetDelegatesWithFromHeader(): void
    {
        $this->inner->expects('request')
            ->once()
            ->with(
                'GET',
                '/api/v1/tickets',
                Mockery::on(function (array $options) {
                    return ($options['headers']['From'] ?? null) === '1';
                }),
            )
            ->andReturn([]);

        $this->handler->get('/api/v1/tickets');
    }

    public function testPostDelegatesWithFromHeader(): void
    {
        $this->inner->expects('request')
            ->once()
            ->with(
                'POST',
                '/api/v1/tickets',
                Mockery::on(function (array $options) {
                    return ($options['headers']['From'] ?? null) === '1'
                        && ($options['headers']['Content-Type'] ?? null) === 'application/json';
                }),
            )
            ->andReturn([]);

        $this->handler->post('/api/v1/tickets', ['title' => 'Test']);
    }

    public function testPutDelegatesWithFromHeader(): void
    {
        $this->inner->expects('request')
            ->once()
            ->with(
                'PUT',
                '/api/v1/tickets/1',
                Mockery::on(function (array $options) {
                    return ($options['headers']['From'] ?? null) === '1'
                        && ($options['headers']['Content-Type'] ?? null) === 'application/json';
                }),
            )
            ->andReturn([]);

        $this->handler->put('/api/v1/tickets/1', ['title' => 'Updated']);
    }

    public function testDeleteDelegatesWithFromHeader(): void
    {
        $this->inner->expects('request')
            ->once()
            ->with(
                'DELETE',
                '/api/v1/tickets/1',
                Mockery::on(function (array $options) {
                    return ($options['headers']['From'] ?? null) === '1';
                }),
            )
            ->andReturn([]);

        $this->handler->delete('/api/v1/tickets/1');
    }

    public function testGetRawDelegatesWithFromHeader(): void
    {
        $this->inner->expects('getRaw')
            ->once()
            ->with(
                '/api/v1/ticket_attachment/1/2/3',
                [],
                Mockery::on(function (array $headers) {
                    return ($headers['From'] ?? null) === '1';
                }),
            )
            ->andReturn('binary-content');

        $result = $this->handler->getRaw('/api/v1/ticket_attachment/1/2/3');

        self::assertSame('binary-content', $result);
    }

    public function testGetLastResponseDelegates(): void
    {
        $response = Mockery::mock(ResponseInterface::class);

        $this->inner->expects('getLastResponse')
            ->once()
            ->andReturn($response);

        self::assertSame($response, $this->handler->getLastResponse());
    }

    public function testInnerHandlerIsNeverMutated(): void
    {
        $this->inner->allows('request')->andReturn([]);
        $this->inner->allows('getRaw')->andReturn('');
        $this->inner->allows('getLastResponse')->andReturn(null);

        $this->handler->get('/api/v1/tickets');
        $this->handler->post('/api/v1/tickets', []);
        $this->handler->put('/api/v1/tickets/1', []);
        $this->handler->delete('/api/v1/tickets/1');
        $this->handler->getRaw('/api/v1/attachment/1/2/3');
        $this->handler->getLastResponse();

        $this->inner->shouldNotHaveReceived('setOnBehalfOfUser');
        $this->inner->shouldNotHaveReceived('getOnBehalfOfUser');

        self::assertTrue(true);
    }

    public function testRequestPreservesOptionsExceptFromHeader(): void
    {
        $this->inner->expects('request')
            ->once()
            ->with(
                'POST',
                'tickets',
                Mockery::on(function (array $options) {
                    return ($options['headers']['From'] ?? null) === '1'
                        && $options['headers']['Content-Type'] === 'application/json'
                        && $options['body'] === '{"title":"Test"}';
                }),
            )
            ->andReturn([]);

        $this->handler->post('tickets', ['title' => 'Test']);
    }
}
