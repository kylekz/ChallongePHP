<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Reflex\Challonge\Auth\ApiKeyAuth;
use Reflex\Challonge\Challonge;
use Reflex\Challonge\DTO\Tournament;

/**
 * Tests PSR-18 HTTP Client compliance
 *
 * Verifies that ChallongePHP works correctly with any PSR-18 compliant HTTP client
 */
class Psr18ComplianceTest extends TestCase
{
    /**
     * Test that the package accepts any PSR-18 ClientInterface implementation
     */
    public function testAcceptsPsr18Client(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $auth = new ApiKeyAuth('test_api_key');

        $challonge = new Challonge($client, $auth);

        $this->assertInstanceOf(Challonge::class, $challonge);
    }

    /**
     * Test that requests use PSR-7 RequestInterface
     */
    public function testSendsPsr7Request(): void
    {
        $json = file_get_contents(__DIR__ . '/Fixtures/Tournament.json');

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) {
                return $request instanceof RequestInterface;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $json));

        $auth = new ApiKeyAuth('test_api_key');
        $challonge = new Challonge($client, $auth);

        $tournament = $challonge->fetchTournament('example_tournament');
        $this->assertInstanceOf(Tournament::class, $tournament);
    }

    /**
     * Test that requests include proper headers
     */
    public function testIncludesProperHeaders(): void
    {
        $json = file_get_contents(__DIR__ . '/Fixtures/Tournament.json');

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                // Check Content-Type header
                $this->assertEquals(['application/vnd.api+json'], $request->getHeader('Content-Type'));

                // Check Accept header
                $this->assertEquals(['application/json'], $request->getHeader('Accept'));

                // Check Authorization header exists
                $this->assertNotEmpty($request->getHeader('Authorization'));

                // Check User-Agent header
                $userAgent = $request->getHeader('User-Agent');
                $this->assertNotEmpty($userAgent);
                $this->assertStringContainsString('ChallongePHP', $userAgent[0]);

                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $json));

        $auth = new ApiKeyAuth('test_api_key');
        $challonge = new Challonge($client, $auth);

        $challonge->fetchTournament('example_tournament');
    }

    /**
     * Test that requests use correct HTTP methods
     */
    public function testUsesCorrectHttpMethods(): void
    {
        $json = file_get_contents(__DIR__ . '/Fixtures/Tournament.json');

        // Test GET request
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                $this->assertEquals('GET', $request->getMethod());
                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $json));

        $auth = new ApiKeyAuth('test_api_key');
        $challonge = new Challonge($client, $auth);
        $challonge->fetchTournament('example_tournament');
    }

    /**
     * Test that POST requests include body
     */
    public function testPostRequestsIncludeBody(): void
    {
        $json = file_get_contents(__DIR__ . '/Fixtures/Tournament.json');

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                $this->assertEquals('POST', $request->getMethod());

                // Verify body is valid JSON
                $body = (string) $request->getBody();
                $this->assertNotEmpty($body);

                $decoded = json_decode($body, true);
                $this->assertIsArray($decoded);
                $this->assertArrayHasKey('data', $decoded);

                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $json));

        $auth = new ApiKeyAuth('test_api_key');
        $challonge = new Challonge($client, $auth);

        $challonge->createTournament([
            'name' => 'Test Tournament',
            'url' => 'test_tournament',
        ]);
    }

    /**
     * Test that PUT requests work correctly for updates
     */
    public function testPutRequestsWorkForUpdates(): void
    {
        $json = file_get_contents(__DIR__ . '/Fixtures/Tournament.json');

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use ($json) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 2) {
                    // Second call is the PUT (API v2.1 uses PUT for updates)
                    $this->assertEquals('PUT', $request->getMethod());
                }

                return new Response(200, ['Content-Type' => 'application/json'], $json);
            });

        $auth = new ApiKeyAuth('test_api_key');
        $challonge = new Challonge($client, $auth);

        $tournament = $challonge->fetchTournament('example_tournament');
        $tournament->update(['name' => 'Updated Name']);
    }

    /**
     * Test that DELETE requests work correctly
     */
    public function testDeleteRequestsWork(): void
    {
        $getTournamentJson = file_get_contents(__DIR__ . '/Fixtures/Tournament.json');

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) use ($getTournamentJson) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 2) {
                    // Second call is the DELETE
                    $this->assertEquals('DELETE', $request->getMethod());
                    return new Response(204); // No content for DELETE
                }

                return new Response(200, ['Content-Type' => 'application/json'], $getTournamentJson);
            });

        $auth = new ApiKeyAuth('test_api_key');
        $challonge = new Challonge($client, $auth);

        $tournament = $challonge->fetchTournament('example_tournament');
        $tournament->delete();
    }

    /**
     * Test that responses are properly handled
     */
    public function testHandlesPsr7Response(): void
    {
        $json = file_get_contents(__DIR__ . '/Fixtures/Tournament.json');

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $json));

        $auth = new ApiKeyAuth('test_api_key');
        $challonge = new Challonge($client, $auth);

        $tournament = $challonge->fetchTournament('example_tournament');

        $this->assertInstanceOf(Tournament::class, $tournament);
        $this->assertEquals(30201, $tournament->id);
    }

    /**
     * Test that error responses are properly handled
     */
    public function testHandlesErrorResponses(): void
    {
        $errorJson = file_get_contents(__DIR__ . '/Fixtures/Error.json');

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willReturn(new Response(422, ['Content-Type' => 'application/json'], $errorJson));

        $auth = new ApiKeyAuth('test_api_key');
        $challonge = new Challonge($client, $auth);

        $this->expectException(\Reflex\Challonge\Exceptions\ValidationException::class);
        $this->expectExceptionMessage('name');

        $challonge->createTournament([
            'url' => 'test',
            // Missing required 'name' field
        ]);
    }

    /**
     * Test that query parameters are properly encoded in URL
     */
    public function testEncodesQueryParameters(): void
    {
        $json = '{"data": []}';

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                $uri = (string) $request->getUri();
                $this->assertStringContainsString('state=', $uri);
                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $json));

        $auth = new ApiKeyAuth('test_api_key');
        $challonge = new Challonge($client, $auth);

        $challonge->getTournaments(['state' => 'pending']);
    }

    /**
     * Test API key authentication header format
     */
    public function testApiKeyAuthHeaderFormat(): void
    {
        $json = file_get_contents(__DIR__ . '/Fixtures/Tournament.json');

        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                $authHeader = $request->getHeader('Authorization');
                $this->assertNotEmpty($authHeader);
                $this->assertEquals('my_test_key_12345', $authHeader[0]);

                // Also check Authorization-Type header
                $authTypeHeader = $request->getHeader('Authorization-Type');
                $this->assertNotEmpty($authTypeHeader);
                $this->assertEquals('v1', $authTypeHeader[0]);

                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $json));

        $auth = new ApiKeyAuth('my_test_key_12345');
        $challonge = new Challonge($client, $auth);

        $challonge->fetchTournament('example_tournament');
    }
}
