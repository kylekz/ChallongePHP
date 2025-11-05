<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Reflex\Challonge\Auth\ApiKeyAuth;
use Reflex\Challonge\Challonge;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Client wrapping and PSR-18 compliance tests
 */
class ClientTest extends TestCase
{
    /**
     * Test if the underlying client is wrapping correctly.
     */
    public function test_client_wrapping(): void
    {
        $client = new Psr18Client();
        $auth = new ApiKeyAuth('test_api_key');
        $challonge = new Challonge($client, $auth);

        $this->assertEquals($client, $challonge->getClient()->getClient());
    }

    /**
     * Test PSR-18 compliance.
     */
    public function test_psr18_compliance(): void
    {
        $mockResponse = new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/TournamentUnderway.json'));

        // set httplug mock
        $httplug = new MockClient();
        $httplug->setDefaultResponse($mockResponse);

        // get httplug response
        $auth = new ApiKeyAuth('test_api_key');
        $challonge = new Challonge($httplug, $auth);
        $httplugResponse = $challonge->createTournament();
        $this->assertSame(
            json_decode((string)$mockResponse->getBody())->data->attributes->name,
            $httplugResponse->name,
        );

        // set guzzle mock
        $mockHandler = new MockHandler();
        $guzzle = new GuzzleClient([
            'handler' => $mockHandler,
        ]);
        $mockHandler->append($mockResponse);

        // get guzzle response
        $challonge = new Challonge($guzzle, $auth);
        $guzzleResponse = $challonge->createTournament();
        $this->assertSame(
            json_decode((string)$mockResponse->getBody())->data->attributes->name,
            $guzzleResponse->name,
        );

        // check if both clients return the same response
        $this->assertSame($httplugResponse->name, $guzzleResponse->name);
    }
}
