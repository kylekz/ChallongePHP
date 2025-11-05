<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Reflex\Challonge\Auth\ApiKeyAuth;
use Reflex\Challonge\Challonge;
use Reflex\Challonge\DTO\Tournament;

/**
 * Tournament operations tests using modern PSR-18 pattern
 */
class TournamentTest extends TestCase
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

    public function test_tournament_ignore_missing(): void
    {
        $client = $this->createMockClient('TournamentUnderway.json');
        $challonge = $this->createChallonge($client);

        $response = $challonge->fetchTournament('9044420');

        $this->assertEquals('challongephp test', $response->name);
    }

    public function test_tournament_index(): void
    {
        $client = $this->createMockClient('TournamentCollection.json');
        $challonge = $this->createChallonge($client);

        $response = $challonge->getTournaments();

        $this->assertCount(2, $response);
    }

    public function test_tournament_create(): void
    {
        $client = $this->createMockClient('TournamentUnderway.json');
        $challonge = $this->createChallonge($client);

        $response = $challonge->createTournament();

        $this->assertEquals('challongephp test', $response->name);
    }

    public function test_tournament_fetch(): void
    {
        $client = $this->createMockClient('TournamentUnderway.json');
        $challonge = $this->createChallonge($client);

        $response = $challonge->fetchTournament('9044420');

        $this->assertEquals('challongephp test', $response->name);
    }

    public function test_tournament_delete(): void
    {
        // Mock DELETE response (204 No Content)
        $mock = $this->createMock(ClientInterface::class);
        $mock->method('sendRequest')
            ->willReturn(new Response(204, []));

        $challonge = new Challonge($mock, new ApiKeyAuth('test_api_key'));

        $tournament = Tournament::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/TournamentUnderway.json'), true)['data']
        );

        $tournament->delete();

        // Assert that delete completed without exception
        $this->assertTrue(true);
    }

    public function test_tournament_start(): void
    {
        $json = file_get_contents(__DIR__ . '/Fixtures/TournamentUnderway.json');

        $mock = $this->createMock(ClientInterface::class);
        $mock->method('sendRequest')
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $json));

        $challonge = new Challonge($mock, new ApiKeyAuth('test_api_key'));

        $tournament = Tournament::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/Tournament.json'), true)['data']
        );

        $response = $tournament->start();

        $this->assertEquals(\Reflex\Challonge\Enums\TournamentState::UNDERWAY, $response->state);
    }

    public function test_tournament_finalize(): void
    {
        $json = file_get_contents(__DIR__ . '/Fixtures/TournamentComplete.json');

        $mock = $this->createMock(ClientInterface::class);
        $mock->method('sendRequest')
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $json));

        $challonge = new Challonge($mock, new ApiKeyAuth('test_api_key'));

        $tournament = Tournament::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/TournamentAwaitingReview.json'), true)['data']
        );

        $response = $tournament->finalize();

        $this->assertEquals(\Reflex\Challonge\Enums\TournamentState::COMPLETE, $response->state);
    }

    public function test_tournament_reset(): void
    {
        $client = $this->createMockClient('TournamentUnderway.json');
        $challonge = $this->createChallonge($client);

        $tournament = Tournament::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/TournamentUnderway.json'), true)['data']
        );

        $response = $tournament->reset();

        $this->assertEquals('challongephp test', $response->name);
    }

    public function test_tournament_update(): void
    {
        $client = $this->createMockClient('TournamentUnderway.json');
        $challonge = $this->createChallonge($client);

        $tournament = Tournament::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/TournamentUnderway.json'), true)['data']
        );

        $response = $tournament->update();

        $this->assertEquals('challongephp test', $response->name);
    }

    public function test_tournament_delete_self(): void
    {
        // Mock DELETE response (204 No Content)
        $mock = $this->createMock(ClientInterface::class);
        $mock->method('sendRequest')
            ->willReturn(new Response(204, []));

        $challonge = new Challonge($mock, new ApiKeyAuth('test_api_key'));

        $tournament = Tournament::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/TournamentUnderway.json'), true)['data']
        );

        $tournament->delete();

        // Assert that delete completed without exception
        $this->assertTrue(true);
    }

    public function test_tournament_clear(): void
    {
        $client = $this->createMockClient('TournamentUnderway.json');
        $challonge = $this->createChallonge($client);

        $tournament = Tournament::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/TournamentUnderway.json'), true)['data']
        );

        $response = $tournament->clear();

        $this->assertEquals('challongephp test', $response->name);
    }

    public function test_tournament_process_checkins(): void
    {
        $client = $this->createMockClient('TournamentUnderway.json');
        $challonge = $this->createChallonge($client);

        $tournament = Tournament::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/Tournament.json'), true)['data']
        );

        $response = $tournament->processCheckins();

        $this->assertEquals('challongephp test', $response->name);
    }

    public function test_tournament_abort_checkins(): void
    {
        $client = $this->createMockClient('TournamentUnderway.json');
        $challonge = $this->createChallonge($client);

        $tournament = Tournament::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/Tournament.json'), true)['data']
        );

        $response = $tournament->abortCheckins();

        $this->assertEquals('challongephp test', $response->name);
    }

    public function test_tournament_add_participant(): void
    {
        $client = $this->createMockClient('Participant.json');
        $challonge = $this->createChallonge($client);

        $tournament = Tournament::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/TournamentUnderway.json'), true)['data']
        );

        $response = $tournament->addParticipant();

        $this->assertEquals('Player 1', $response->display_name);
    }

    public function test_tournament_bulkadd_participant(): void
    {
        $client = $this->createMockClient('ParticipantCollection.json');
        $challonge = $this->createChallonge($client);

        $tournament = Tournament::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/TournamentUnderway.json'), true)['data']
        );

        $response = $tournament->bulkAddParticipant([
            ['name' => 'Team 1'],
            ['name' => 'Team 2'],
        ]);

        $this->assertCount(3, $response);
    }

    public function test_tournament_delete_participant(): void
    {
        // Mock DELETE response (204 No Content)
        $mock = $this->createMock(ClientInterface::class);
        $mock->method('sendRequest')
            ->willReturn(new Response(204, []));

        $challonge = new Challonge($mock, new ApiKeyAuth('test_api_key'));

        $tournament = Tournament::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/Tournament.json'), true)['data']
        );

        $tournament->deleteParticipant(1);

        // Assert that delete completed without exception
        $this->assertTrue(true);
    }

    public function test_tournament_update_participant(): void
    {
        $client = $this->createMockClient('Participant.json');
        $challonge = $this->createChallonge($client);

        $tournament = Tournament::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/Tournament.json'), true)['data']
        );

        $response = $tournament->updateParticipant(1);

        $this->assertEquals('Player 1', $response->display_name);
    }

    public function test_tournament_standings(): void
    {
        // Create a mock that returns different responses for sequential calls
        $mock = $this->createMock(ClientInterface::class);
        $mock->expects($this->exactly(2))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls(
                new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__ . '/Fixtures/ParticipantCollection.json')),
                new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__ . '/Fixtures/MatchCollection.json'))
            );

        $challonge = new Challonge($mock, new ApiKeyAuth('test_api_key'));
        $response = $challonge->getStandings('challongephptest');

        $this->assertEquals('Team 1', $response['final'][0]['name']);
    }
}
