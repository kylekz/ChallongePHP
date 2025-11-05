<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Reflex\Challonge\Auth\ApiKeyAuth;
use Reflex\Challonge\Challonge;
use Reflex\Challonge\DTO\MatchDto;

/**
 * Match operations tests using modern PSR-18 pattern
 */
class MatchTest extends TestCase
{
    private function createMockClient(string $fixtureFile): ClientInterface
    {
        $json = file_get_contents(__DIR__ . '/Fixtures/' . $fixtureFile);

        $mock = $this->createMock(ClientInterface::class);
        $mock->method('sendRequest')
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $json));

        return $mock;
    }

    private function createChallonge(ClientInterface $client): Challonge
    {
        $auth = new ApiKeyAuth('test_api_key');
        return new Challonge($client, $auth);
    }

    public function test_match_index(): void
    {
        $client = $this->createMockClient('MatchCollection.json');
        $challonge = $this->createChallonge($client);

        $response = $challonge->getMatches('challongephptest');

        $this->assertCount(2, $response);
    }

    public function test_match_fetch(): void
    {
        $client = $this->createMockClient('Match.json');
        $challonge = $this->createChallonge($client);

        $response = $challonge->getMatch('challongephptest', 98765);

        $this->assertEquals(98765, $response->id);
    }

    public function test_match_update(): void
    {
        $client = $this->createMockClient('Match.json');
        $challonge = $this->createChallonge($client);

        $match = MatchDto::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/Match.json'), true)['data']
        );

        $response = $match->update();

        $this->assertEquals(98765, $response->id);
    }

    public function test_match_reopen(): void
    {
        $client = $this->createMockClient('Match.json');
        $challonge = $this->createChallonge($client);

        $match = MatchDto::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/Match.json'), true)['data']
        );

        $response = $match->reopen();

        $this->assertEquals(98765, $response->id);
    }

    public function test_match_mark_underway(): void
    {
        $client = $this->createMockClient('Match.json');
        $challonge = $this->createChallonge($client);

        $match = MatchDto::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/Match.json'), true)['data']
        );

        $response = $match->markAsUnderway();

        $this->assertEquals(98765, $response->id);
    }

    public function test_match_unmark_underway(): void
    {
        $client = $this->createMockClient('Match.json');
        $challonge = $this->createChallonge($client);

        $match = MatchDto::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/Match.json'), true)['data']
        );

        $response = $match->unmarkAsUnderway();

        $this->assertEquals(98765, $response->id);
    }
}
