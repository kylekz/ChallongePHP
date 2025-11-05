<?php

declare(strict_types=1);

namespace Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Reflex\Challonge\Auth\ApiKeyAuth;
use Reflex\Challonge\Challonge;
use Reflex\Challonge\DTO\Attachment;
use Reflex\Challonge\DTO\Community;
use Reflex\Challonge\DTO\ElapsedTime;
use Reflex\Challonge\DTO\MatchDto;
use Reflex\Challonge\DTO\Participant;
use Reflex\Challonge\DTO\Race;
use Reflex\Challonge\DTO\Round;
use Reflex\Challonge\DTO\Tournament;
use Reflex\Challonge\DTO\User;

/**
 * Comprehensive entity tests for all Challonge API v2.1 entities
 */
class EntityTest extends TestCase
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

    // ==================== TOURNAMENT TESTS ====================

    public function testFetchTournament(): void
    {
        $client = $this->createMockClient('Tournament.json');
        $challonge = $this->createChallonge($client);

        $tournament = $challonge->fetchTournament('example_tournament');

        $this->assertInstanceOf(Tournament::class, $tournament);
        $this->assertEquals(30201, $tournament->id);
        $this->assertEquals('Example Tournament', $tournament->name);
        $this->assertEquals('example_tournament', $tournament->url);
        $this->assertEquals('single_elimination', $tournament->tournament_type);
        $this->assertEquals('pending', $tournament->state);
        $this->assertEquals(8, $tournament->participants_count);
        $this->assertEquals(16, $tournament->signup_cap);
        $this->assertTrue($tournament->notify_users_when_matches_open);
        $this->assertFalse($tournament->private);
        $this->assertTrue($tournament->created_by_api);
    }

    public function testTournamentHasReadonlyProperties(): void
    {
        $client = $this->createMockClient('Tournament.json');
        $challonge = $this->createChallonge($client);
        $tournament = $challonge->fetchTournament('example_tournament');

        $this->expectException(\Error::class);
        $tournament->name = 'New Name'; // Should fail - readonly property
    }

    // ==================== PARTICIPANT TESTS ====================

    public function testFetchParticipant(): void
    {
        $client = $this->createMockClient('Participant.json');
        $challonge = $this->createChallonge($client);

        $participant = $challonge->getParticipant('example_tournament', 12345);

        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertEquals(12345, $participant->id);
        $this->assertEquals(30201, $participant->tournament_id);
        $this->assertEquals('Player 1', $participant->name);
        $this->assertEquals(1, $participant->seed);
        $this->assertTrue($participant->active);
        $this->assertFalse($participant->on_waiting_list);
        $this->assertFalse($participant->checked_in);
        $this->assertTrue($participant->can_check_in);
        $this->assertTrue($participant->removable);
        $this->assertEquals('Some notes', $participant->misc);
    }

    // ==================== MATCH TESTS ====================

    public function testFetchMatch(): void
    {
        $client = $this->createMockClient('Match.json');
        $challonge = $this->createChallonge($client);

        $match = $challonge->getMatch('example_tournament', 98765);

        $this->assertInstanceOf(MatchDto::class, $match);
        $this->assertEquals(98765, $match->id);
        $this->assertEquals(30201, $match->tournament_id);
        $this->assertEquals('A', $match->identifier);
        $this->assertEquals('open', $match->state);
        $this->assertEquals(12345, $match->player1_id);
        $this->assertEquals(12346, $match->player2_id);
        $this->assertNull($match->winner_id);
        $this->assertFalse($match->forfeited);
        $this->assertEquals(1, $match->round);
        $this->assertFalse($match->has_attachment);
    }

    // ==================== ATTACHMENT TESTS ====================

    public function testFetchAttachment(): void
    {
        $client = $this->createMockClient('Attachment.json');
        $challonge = $this->createChallonge($client);

        $attachment = $challonge->getMatchAttachment('example_tournament', 98765, 332211);

        $this->assertInstanceOf(Attachment::class, $attachment);
        $this->assertEquals(332211, $attachment->id);
        $this->assertEquals(98765, $attachment->match_id);
        $this->assertEquals(54321, $attachment->user_id);
        $this->assertEquals('Screenshot from final match', $attachment->description);
        $this->assertEquals('https://example.com/screenshot.png', $attachment->url);
        $this->assertEquals('screenshot.png', $attachment->original_file_name);
        $this->assertEquals('image/png', $attachment->asset_content_type);
        $this->assertEquals(245678, $attachment->asset_file_size);
    }

    // ==================== RACE TESTS ====================

    public function testFetchRace(): void
    {
        $client = $this->createMockClient('Race.json');
        $challonge = $this->createChallonge($client);

        $race = $challonge->fetchRace('speedrun_championship');

        $this->assertInstanceOf(Race::class, $race);
        $this->assertEquals(50301, $race->id);
        $this->assertEquals('Speed Run Championship', $race->name);
        $this->assertEquals('speedrun_championship', $race->url);
        $this->assertEquals('pending', $race->state);
        $this->assertEquals('time_trial', $race->race_type);
        $this->assertEquals(3, $race->target_round_count);
        $this->assertEquals(0, $race->current_round);
        $this->assertEquals(16, $race->participants_count);
        $this->assertEquals(32, $race->signup_cap);
        $this->assertTrue($race->created_by_api);
        $this->assertEquals('Super Speed Runner', $race->game_name);
    }

    // ==================== ROUND TESTS ====================

    public function testFetchRound(): void
    {
        $client = $this->createMockClient('Round.json');
        $challonge = $this->createChallonge($client);

        $round = $challonge->getRaceRound('speedrun_championship', 60401);

        $this->assertInstanceOf(Round::class, $round);
        $this->assertEquals(60401, $round->id);
        $this->assertEquals(50301, $round->race_id);
        $this->assertEquals(1, $round->number);
        $this->assertEquals('open', $round->state);
        $this->assertEquals('Round 1', $round->name);
        $this->assertEquals(8, $round->elapsed_times_count);
        $this->assertNotNull($round->started_at);
        $this->assertNull($round->completed_at);
    }

    // ==================== ELAPSED TIME TESTS ====================

    public function testFetchElapsedTime(): void
    {
        $client = $this->createMockClient('ElapsedTime.json');
        $challonge = $this->createChallonge($client);

        $time = $challonge->getRoundElapsedTime('speedrun_championship', 60401, 70501);

        $this->assertInstanceOf(ElapsedTime::class, $time);
        $this->assertEquals(70501, $time->id);
        $this->assertEquals(60401, $time->round_id);
        $this->assertEquals(12345, $time->participant_id);
        $this->assertEquals(125430, $time->elapsed_time_millis);
        $this->assertEquals(125.43, $time->elapsed_time_seconds);
        $this->assertEquals('2:05.430', $time->display_time);
        $this->assertEquals(1, $time->rank);
        $this->assertFalse($time->disqualified);
        $this->assertFalse($time->dnf);
        $this->assertFalse($time->dns);
    }

    // ==================== COMMUNITY TESTS ====================

    public function testFetchCommunity(): void
    {
        $client = $this->createMockClient('Community.json');
        $challonge = $this->createChallonge($client);

        $community = $challonge->getCommunity('esports_champions');

        $this->assertInstanceOf(Community::class, $community);
        $this->assertEquals(80601, $community->id);
        $this->assertEquals('Esports Champions', $community->name);
        $this->assertEquals('esports_champions', $community->url);
        $this->assertEquals('esports_champions', $community->identifier);
        $this->assertEquals('Premier esports community', $community->description);
        $this->assertFalse($community->private);
        $this->assertFalse($community->hide_global_chat);
        $this->assertEquals(250, $community->member_count);
        $this->assertEquals(42, $community->tournaments_count);
        $this->assertNotNull($community->banner_url);
        $this->assertNotNull($community->icon_url);
    }

    // ==================== USER TESTS ====================

    public function testFetchUser(): void
    {
        $client = $this->createMockClient('User.json');
        $challonge = $this->createChallonge($client);

        $user = $challonge->getMe();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(90701, $user->id);
        $this->assertEquals('tournament_organizer', $user->username);
        $this->assertEquals('Tournament Organizer', $user->display_name);
        $this->assertEquals('organizer@example.com', $user->email);
        $this->assertEquals('Professional tournament organizer', $user->bio);
        $this->assertEquals('San Francisco, CA', $user->location);
        $this->assertEquals('https://example.com', $user->website);
        $this->assertTrue($user->email_verified);
        $this->assertEquals('America/Los_Angeles', $user->timezone);
        $this->assertEquals('en-US', $user->locale);
        $this->assertEquals(156, $user->tournaments_count);
        $this->assertEquals(5, $user->communities_count);
    }

    // ==================== TYPE SAFETY TESTS ====================

    public function testAllEntitiesAreImmutable(): void
    {
        $entities = [
            'Tournament.json' => fn($c) => $c->fetchTournament('test'),
            'Participant.json' => fn($c) => $c->getParticipant('test', 1),
            'Match.json' => fn($c) => $c->getMatch('test', 1),
            'Attachment.json' => fn($c) => $c->getMatchAttachment('test', 1, 1),
            'Race.json' => fn($c) => $c->fetchRace('test'),
            'Round.json' => fn($c) => $c->getRaceRound('test', 1),
            'ElapsedTime.json' => fn($c) => $c->getRoundElapsedTime('test', 1, 1),
            'Community.json' => fn($c) => $c->getCommunity('test'),
            'User.json' => fn($c) => $c->getMe(),
        ];

        foreach ($entities as $fixture => $getter) {
            $client = $this->createMockClient($fixture);
            $challonge = $this->createChallonge($client);
            $entity = $getter($challonge);

            $reflection = new \ReflectionClass($entity);
            $properties = $reflection->getProperties();

            foreach ($properties as $property) {
                if ($property->isPublic() && !$property->isStatic()) {
                    $this->assertTrue(
                        $property->isReadOnly(),
                        "Property {$property->getName()} in " . get_class($entity) . " should be readonly"
                    );
                }
            }
        }
    }
}
