<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Reflex\Challonge\DtoClientTrait;
use Reflex\Challonge\Enums\MatchState;

/**
 * Match DTO
 *
 * Properties are typed based on Challonge API v2.1 swagger specification.
 */
class MatchDto
{
    use DtoClientTrait;

    public function __construct(
        // REQUIRED PARAMETERS (no defaults) - must come first
        // Core identifiers - always present
        public readonly int $id,
        public readonly int $tournament_id,
        public readonly string $identifier,
        public readonly MatchState $state,
        
        // Timestamps - always present
        public readonly string $created_at,
        public readonly string $updated_at,

        // OPTIONAL PARAMETERS WITH DEFAULTS
        // Round/ordering - have defaults
        public readonly int $round = 1,
        public readonly int $suggested_play_order = 1,

        // Boolean flags - have defaults
        public readonly bool $optional = false,
        public readonly bool $player1_is_prereq_match_loser = false,
        public readonly bool $player2_is_prereq_match_loser = false,
        public readonly bool $has_attachment = false,

        // Scores - empty string default
        public readonly string $scores_csv = '',
        public readonly string $prerequisite_match_ids_csv = '',

        // NULLABLE OPTIONAL PARAMETERS
        // Optional boolean (can be null)
        public readonly ?bool $forfeited = null,

        // Optional counts (can be null)
        public readonly ?int $attachment_count = null,

        // Optional player IDs (can be null before seeding)
        public readonly ?int $player1_id = null,
        public readonly ?int $player2_id = null,

        // Optional prerequisite matches
        public readonly ?int $player1_prereq_match_id = null,
        public readonly ?int $player2_prereq_match_id = null,

        // Optional votes
        public readonly ?int $player1_votes = null,
        public readonly ?int $player2_votes = null,

        // Optional results (only set when match complete)
        public readonly ?int $winner_id = null,
        public readonly ?int $loser_id = null,

        // Optional scheduling
        public readonly ?string $scheduled_time = null,
        public readonly ?string $location = null,

        // Optional timestamps
        public readonly ?string $completed_at = null,
        public readonly ?string $started_at = null,
        public readonly ?string $underway_at = null,

        // Optional group/tournament context
        public readonly ?int $group_id = null,
        public readonly ?int $rushb_id = null,

        // Optional open graph images
        public readonly ?string $open_graph_image_file_name = null,
        public readonly ?string $open_graph_image_content_type = null,
        public readonly ?string $open_graph_image_file_size = null,
    ) {
    }

    /**
     * Update/submit the score(s) for a match
     *
     * @param array<string, mixed> $attributes
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function update(array $attributes = []): self
    {
        $response = $this->client->request('PUT', "tournaments/{$this->tournament_id}/matches/{$this->id}", [
            'data' => [
                'type' => 'matches',
                'id' => (string) $this->id,
                'attributes' => $attributes,
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Reopen a match
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function reopen(): self
    {
        $response = $this->client->request('POST', "tournaments/{$this->tournament_id}/matches/{$this->id}/change_state", [
            'data' => [
                'type' => 'matches',
                'id' => (string) $this->id,
                'attributes' => [
                    'state' => 'reopen',
                ],
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Mark a match as underway, highlights it in the bracket
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function markAsUnderway(): self
    {
        $response = $this->client->request('POST', "tournaments/{$this->tournament_id}/matches/{$this->id}/change_state", [
            'data' => [
                'type' => 'matches',
                'id' => (string) $this->id,
                'attributes' => [
                    'state' => 'mark_as_underway',
                ],
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Unmark a match as underway
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function unmarkAsUnderway(): self
    {
        $response = $this->client->request('POST', "tournaments/{$this->tournament_id}/matches/{$this->id}/change_state", [
            'data' => [
                'type' => 'matches',
                'id' => (string) $this->id,
                'attributes' => [
                    'state' => 'unmark_as_underway',
                ],
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }
}
