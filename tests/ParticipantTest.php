<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Reflex\Challonge\Auth\ApiKeyAuth;
use Reflex\Challonge\Challonge;
use Reflex\Challonge\DTO\Participant;

/**
 * Participant operations tests using modern PSR-18 pattern
 */
class ParticipantTest extends TestCase
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

    public function test_participant_index(): void
    {
        $client = $this->createMockClient('ParticipantCollection.json');
        $challonge = $this->createChallonge($client);

        $response = $challonge->getParticipants('challongephptest');

        $this->assertCount(3, $response);
    }

    public function test_tournament_fetch(): void
    {
        $client = $this->createMockClient('Participant.json');
        $challonge = $this->createChallonge($client);

        $response = $challonge->getParticipant('challongephptest', 12345);

        $this->assertEquals('Player 1', $response->display_name);
    }

    public function test_participant_randomize(): void
    {
        $client = $this->createMockClient('ParticipantCollection.json');
        $challonge = $this->createChallonge($client);

        $response = $challonge->randomizeParticipants('challongephptest');

        $this->assertCount(3, $response);
    }

    public function test_participant_update(): void
    {
        $client = $this->createMockClient('Participant.json');
        $challonge = $this->createChallonge($client);

        $participant = Participant::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/Participant.json'), true)['data']
        );

        $response = $participant->update();

        $this->assertEquals('Player 1', $response->display_name);
    }

    public function test_participant_delete(): void
    {
        // Mock DELETE response (204 No Content)
        $mock = $this->createMock(ClientInterface::class);
        $mock->method('sendRequest')
            ->willReturn(new Response(204, []));

        $challonge = new Challonge($mock, new ApiKeyAuth('test_api_key'));

        $participant = Participant::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/Participant.json'), true)['data']
        );

        $participant->delete();

        // Assert that delete completed without exception
        $this->assertTrue(true);
    }

    public function test_participant_checkin(): void
    {
        $client = $this->createMockClient('Participant.json');
        $challonge = $this->createChallonge($client);

        $participant = Participant::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/Participant.json'), true)['data']
        );

        $response = $participant->checkin();

        $this->assertEquals('Player 1', $response->display_name);
    }

    public function test_participant_undo_checkin(): void
    {
        $client = $this->createMockClient('Participant.json');
        $challonge = $this->createChallonge($client);

        $participant = Participant::fromResponse(
            $challonge->getClient(),
            json_decode(file_get_contents(__DIR__ . '/Fixtures/Participant.json'), true)['data']
        );

        $response = $participant->undoCheckin();

        $this->assertEquals('Player 1', $response->display_name);
    }
}
