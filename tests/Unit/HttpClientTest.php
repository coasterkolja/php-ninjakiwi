<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Kan\NkOpendata\Exceptions\ApiException;
use Kan\NkOpendata\Http\HttpClient;
use PHPUnit\Framework\TestCase;

final class HttpClientTest extends TestCase
{
    private function createClientWithMockHandler(MockHandler $mock): HttpClient
    {
        $handlerStack = HandlerStack::create($mock);
        $guzzle = new Client(['handler' => $handlerStack]);

        $http = new HttpClient('https://example.com');
        $prop = new \ReflectionProperty(HttpClient::class, 'client');
        $prop->setValue($http, $guzzle);

        return $http;
    }

    public function test_successful_request_returns_body(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => true,
                'body' => ['id' => '123', 'name' => 'Test'],
            ])),
        ]);

        $http = $this->createClientWithMockHandler($mock);
        $result = $http->get('test');

        $this->assertIsArray($result);
        $this->assertSame('123', $result['body']['id']);
    }

    public function test_api_error_response_throws_exception(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'success' => false,
                'error' => 'Not found',
            ])),
        ]);

        $http = $this->createClientWithMockHandler($mock);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Api call failed');

        $http->get('test');
    }

    public function test_non_array_response_throws_exception(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['invalid'])),
        ]);

        $http = $this->createClientWithMockHandler($mock);

        $this->expectException(ApiException::class);

        $http->get('test');
    }

    public function test_empty_body_throws_exception(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([])),
        ]);

        $http = $this->createClientWithMockHandler($mock);

        $this->expectException(ApiException::class);

        $http->get('test');
    }

    public function test_http_exception_throws_api_exception(): void
    {
        $mock = new MockHandler([
            new \GuzzleHttp\Exception\ConnectException(
                'Connection failed',
                new \GuzzleHttp\Psr7\Request('GET', 'test'),
            ),
        ]);

        $http = $this->createClientWithMockHandler($mock);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Connection failed');

        $http->get('test');
    }
}
