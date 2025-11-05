<?php

declare(strict_types=1);

namespace Reflex\Challonge;

use Illuminate\Support\Collection;
use Psr\Http\Client\ClientInterface;
use Reflex\Challonge\Auth\ApiKeyAuth;
use Reflex\Challonge\Auth\AuthenticationInterface;
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
 * Challonge API v2.1 Client
 *
 * PSR-18 compatible client for the Challonge tournament management API.
 * Supports both v1 API keys and OAuth v2 authentication.
 */
class Challonge
{
    protected string $version = '6.0.0';
    protected ClientWrapper $client;

    /**
     * Create a new Challonge API client
     *
     * @param ClientInterface $httpClient PSR-18 HTTP client
     * @param AuthenticationInterface|string $auth Authentication provider or API key string
     * @param bool $mapOptions Enable legacy option mapping (defaults to false for v2.1)
     */
    public function __construct(
        ClientInterface $httpClient,
        AuthenticationInterface|string $auth = '',
        bool $mapOptions = false
    ) {
        // Convert string API key to ApiKeyAuth for backwards compatibility
        if (is_string($auth)) {
            $auth = new ApiKeyAuth($auth);
        }

        $this->client = new ClientWrapper($httpClient, $auth, $this->version, $mapOptions);
    }

    /**
     * Get the underlying client wrapper
     */
    public function getClient(): ClientWrapper
    {
        return $this->client;
    }

    /**
     * Retrieve a set of tournaments
     *
     * @param array<string, mixed> $filters Optional filters (state, type, created_after, created_before, etc.)
     * @return Collection<int, Tournament>
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getTournaments(array $filters = []): Collection
    {
        $response = $this->client->request('GET', 'tournaments', [], $filters);
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $tournament) => Tournament::fromResponse($this->client, $tournament));
    }

    /**
     * Create a new tournament
     *
     * @param array<string, mixed> $attributes Tournament attributes
     * @return Tournament
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function createTournament(array $attributes = []): Tournament
    {
        $response = $this->client->request('POST', 'tournaments', [
            'data' => [
                'type' => 'tournaments',
                'attributes' => $attributes,
            ],
        ]);

        return Tournament::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Retrieve a single tournament
     *
     * @param string $tournament Tournament ID or URL
     * @return Tournament
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function fetchTournament(string $tournament): Tournament
    {
        $response = $this->client->request('GET', "tournaments/{$tournament}");

        return Tournament::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Delete a tournament and all its records
     *
     * @param string $tournament Tournament ID or URL
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function deleteTournament(string $tournament): void
    {
        $this->client->request('DELETE', "tournaments/{$tournament}");
    }

    /**
     * Retrieve a tournament's participant list
     *
     * @param string $tournament Tournament ID or URL
     * @return Collection<int, Participant>
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getParticipants(string $tournament): Collection
    {
        $response = $this->client->request('GET', "tournaments/{$tournament}/participants");
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $participant) => Participant::fromResponse($this->client, $participant));
    }

    /**
     * Randomize seeds among participants
     *
     * @param string $tournament Tournament ID or URL
     * @return Collection<int, Participant>
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function randomizeParticipants(string $tournament): Collection
    {
        $response = $this->client->request('POST', "tournaments/{$tournament}/participants/randomize");
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $participant) => Participant::fromResponse($this->client, $participant));
    }

    /**
     * Retrieve a single participant
     *
     * @param string $tournament Tournament ID or URL
     * @param int $participant Participant ID
     * @return Participant
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getParticipant(string $tournament, int $participant): Participant
    {
        $response = $this->client->request('GET', "tournaments/{$tournament}/participants/{$participant}");

        return Participant::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Retrieve a tournament's match list
     *
     * @param string $tournament Tournament ID or URL
     * @param array<string, mixed> $filters Optional filters
     * @return Collection<int, MatchDto>
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getMatches(string $tournament, array $filters = []): Collection
    {
        $response = $this->client->request('GET', "tournaments/{$tournament}/matches", [], $filters);
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $match) => MatchDto::fromResponse($this->client, $match));
    }

    /**
     * Retrieve a single match
     *
     * @param string $tournament Tournament ID or URL
     * @param int $match Match ID
     * @return MatchDto
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getMatch(string $tournament, int $match): MatchDto
    {
        $response = $this->client->request('GET', "tournaments/{$tournament}/matches/{$match}");

        return MatchDto::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Retrieve a leaderboard/standings for a tournament
     *
     * This method calculates standings based on match results, including:
     * - Progress percentage
     * - Win/loss/tie records
     * - Points scored
     * - Match history
     *
     * @param string $tournament Tournament ID or URL
     * @return Collection<string, mixed>
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getStandings(string $tournament): Collection
    {
        $participants = $this->getParticipants($tournament);
        $matches = $this->getMatches($tournament);

        $matchesComplete = $matches->where('state', 'complete')->count();
        $matchesTotal = $matches->count();

        $result = [
            'progress' => $matchesTotal > 0 ? round(($matchesComplete / $matchesTotal) * 100) : 0,
        ];

        $finals = [];
        $groups = [];

        foreach ($participants as $team) {
            $teamWithResults = $this->getStanding($team, $matches);
            $finals[] = $teamWithResults->final['results'];

            if (!empty($teamWithResults->groups[0])) {
                $groups[] = $teamWithResults->groups[0]['results'];
            }
        }

        if (!empty($finals)) {
            $result['final'] = Collection::make($finals)->sortByDesc('win');
        }

        if (!empty($groups)) {
            $result['groups'] = Collection::make($groups)->sortByDesc('win');
        }

        return Collection::make($result);
    }

    /**
     * Get standing for a participant across all groups and matches
     */
    private function getStanding(Participant $participant, Collection $matches): Participant
    {
        $participantGroups = [];

        foreach ($participant->group_player_ids ?? [] as $playerGroupId) {
            $data = $matches->filter(fn ($item) =>
                in_array($playerGroupId, [$item->player1_id, $item->player2_id], true)
            );

            $participantGroup = [
                'matches' => $data,
                'results' => $this->matchResults($data, $playerGroupId, $participant->name ?? ''),
            ];

            $participantGroups[] = $participantGroup;
        }

        $participantFinal = [
            'matches' => $matches->filter(fn ($item) =>
                $item->player1_id === $participant->id || $item->player2_id === $participant->id
            ),
        ];

        $participantFinal['results'] = $this->matchResults(
            $participantFinal['matches'],
            $participant->id ?? 0,
            $participant->name ?? ''
        );

        // Note: These are dynamic properties for standings calculation
        $participant->groups = $participantGroups;
        $participant->final = $participantFinal;

        return $participant;
    }

    /**
     * Get match results for a given player
     *
     * @return Collection<string, mixed>
     */
    private function matchResults(Collection $matches, int $playerId, string $participantName): Collection
    {
        $result = [
            'win' => 0,
            'lose' => 0,
            'tie' => 0,
            'pts' => 0,
            'history' => [],
            'name' => $participantName,
            'id' => $playerId,
        ];

        foreach ($matches as $match) {
            if ($match->winner_id === $playerId) {
                $result['win'] += 1;
                $result['history'][] = 'W';
            }

            if ($match->loser_id === $playerId) {
                $result['lose'] += 1;
                $result['history'][] = 'L';
            }

            if ($match->loser_id === null && $match->winner_id === null) {
                $result['tie'] += 1;
                $result['history'][] = 'T';
            }

            $pts = $this->getMatchPts($match, $playerId);
            $result['pts'] += $pts->where('type', 'player')->pluck('score')->first() ?? 0;
        }

        return Collection::make($result);
    }

    /**
     * Get match points for a given player
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function getMatchPts(MatchDto $match, int $playerId): Collection
    {
        $playerScore = 0;
        $scores = [0, 0];

        if (!empty($match->scores_csv)) {
            $scores = array_map('intval', explode('-', $match->scores_csv));
            sort($scores);
        }

        if ($match->loser_id === $playerId) {
            $playerScore = $scores[0];
        } elseif ($match->winner_id === $playerId) {
            $playerScore = $scores[1];
        } elseif ($match->loser_id === null && $match->winner_id === null) {
            $playerScore = $scores[0];
        }

        return Collection::make([
            ['type' => 'loser', 'id' => $match->loser_id, 'score' => $scores[0]],
            ['type' => 'winner', 'id' => $match->winner_id, 'score' => $scores[1]],
            ['type' => 'player', 'id' => $playerId, 'score' => $playerScore],
        ]);
    }

    // ==================== RACE METHODS ====================

    /**
     * Get all races
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, Race>
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getRaces(array $filters = []): Collection
    {
        $response = $this->client->request('GET', 'races', [], $filters);
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $race) => Race::fromResponse($this->client, $race));
    }

    /**
     * Create a new race
     *
     * @param array<string, mixed> $attributes
     * @return Race
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function createRace(array $attributes = []): Race
    {
        $response = $this->client->request('POST', 'races', [
            'data' => [
                'type' => 'races',
                'attributes' => $attributes,
            ],
        ]);

        return Race::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Fetch a specific race
     *
     * @param string $race Race ID or URL
     * @return Race
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function fetchRace(string $race): Race
    {
        $response = $this->client->request('GET', "races/{$race}");

        return Race::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Get rounds for a race
     *
     * @param string $race Race ID or URL
     * @return Collection<int, Round>
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getRaceRounds(string $race): Collection
    {
        $response = $this->client->request('GET', "races/{$race}/rounds");
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $round) => Round::fromResponse($this->client, $round));
    }

    /**
     * Get a specific round
     *
     * @param string $race Race ID or URL
     * @param int $round Round ID
     * @return Round
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getRaceRound(string $race, int $round): Round
    {
        $response = $this->client->request('GET', "races/{$race}/rounds/{$round}");

        return Round::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Get elapsed times for a round
     *
     * @param string $race Race ID or URL
     * @param int $round Round ID
     * @return Collection<int, ElapsedTime>
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getRoundElapsedTimes(string $race, int $round): Collection
    {
        $response = $this->client->request('GET', "races/{$race}/rounds/{$round}/elapsed_times");
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $time) => ElapsedTime::fromResponse($this->client, $time));
    }

    /**
     * Get a specific elapsed time
     *
     * @param string $race Race ID or URL
     * @param int $round Round ID
     * @param int $elapsedTime Elapsed time ID
     * @return ElapsedTime
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getRoundElapsedTime(string $race, int $round, int $elapsedTime): ElapsedTime
    {
        $response = $this->client->request('GET', "races/{$race}/rounds/{$round}/elapsed_times/{$elapsedTime}");

        return ElapsedTime::fromResponse($this->client, $response['data'] ?? $response);
    }

    // ==================== ATTACHMENT METHODS ====================

    /**
     * Get attachments for a match
     *
     * @param string $tournament Tournament ID or URL
     * @param int $match Match ID
     * @return Collection<int, Attachment>
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getMatchAttachments(string $tournament, int $match): Collection
    {
        $response = $this->client->request('GET', "tournaments/{$tournament}/matches/{$match}/attachments");
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $attachment) => Attachment::fromResponse($this->client, $attachment));
    }

    /**
     * Get a specific match attachment
     *
     * @param string $tournament Tournament ID or URL
     * @param int $match Match ID
     * @param int $attachment Attachment ID
     * @return Attachment
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getMatchAttachment(string $tournament, int $match, int $attachment): Attachment
    {
        $response = $this->client->request('GET', "tournaments/{$tournament}/matches/{$match}/attachments/{$attachment}");

        return Attachment::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Create a match attachment
     *
     * @param string $tournament Tournament ID or URL
     * @param int $match Match ID
     * @param array<string, mixed> $attributes Attachment attributes (url or file data)
     * @return Attachment
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function createMatchAttachment(string $tournament, int $match, array $attributes = []): Attachment
    {
        $response = $this->client->request('POST', "tournaments/{$tournament}/matches/{$match}/attachments", [
            'data' => [
                'type' => 'attachments',
                'attributes' => $attributes,
            ],
        ]);

        return Attachment::fromResponse($this->client, $response['data'] ?? $response);
    }

    // ==================== COMMUNITY METHODS ====================

    /**
     * Get a community by identifier
     *
     * Note: The Community DTO itself has methods for getting tournaments, participants, etc.
     *
     * @param string $identifier Community identifier/URL
     * @return Community
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getCommunity(string $identifier): Community
    {
        $response = $this->client->request('GET', "communities/{$identifier}");

        return Community::fromResponse($this->client, $response['data'] ?? $response);
    }

    // ==================== USER METHODS ====================

    /**
     * Get the authenticated user's profile
     *
     * Requires OAuth authentication with 'me' scope or API key
     *
     * @return User
     * @throws Exceptions\InvalidFormatException
     * @throws Exceptions\NotFoundException
     * @throws Exceptions\ServerException
     * @throws Exceptions\UnauthorizedException
     * @throws Exceptions\ValidationException
     */
    public function getMe(): User
    {
        $response = $this->client->request('GET', 'me');

        return User::fromResponse($this->client, $response['data'] ?? $response);
    }
}
