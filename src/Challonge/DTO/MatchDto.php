<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Reflex\Challonge\DtoClientTrait;

/**
 * Match DTO
 *
 * Note: All properties are nullable because Challonge's API is not stable
 * and frequently adds/changes fields. The v2.1 API uses JSON API format.
 */
class MatchDto
{
    use DtoClientTrait;

    public function __construct(
        // Core identifiers
        public readonly ?int $id = null,
        public readonly ?int $tournament_id = null,
        public readonly ?string $identifier = null,

        // State
        public readonly ?string $state = null,
        public readonly ?bool $forfeited = null,
        public readonly ?bool $optional = null,

        // Players
        public readonly ?int $player1_id = null,
        public readonly ?bool $player1_is_prereq_match_loser = null,
        public readonly ?int $player1_prereq_match_id = null,
        public readonly ?int $player1_votes = null,
        public readonly ?int $player2_id = null,
        public readonly ?bool $player2_is_prereq_match_loser = null,
        public readonly ?int $player2_prereq_match_id = null,
        public readonly ?int $player2_votes = null,

        // Results
        public readonly ?int $winner_id = null,
        public readonly ?int $loser_id = null,
        public readonly ?string $scores_csv = null,

        // Scheduling
        public readonly ?int $round = null,
        public readonly mixed $suggested_play_order = null,
        public readonly ?string $scheduled_time = null,
        public readonly ?string $location = null,

        // Timestamps
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
        public readonly ?string $completed_at = null,
        public readonly ?string $started_at = null,
        public readonly ?string $underway_at = null,

        // Attachments
        public readonly ?bool $has_attachment = null,
        public readonly ?int $attachment_count = null,

        // Group/Tournament context
        public readonly ?int $group_id = null,
        public readonly ?string $prerequisite_match_ids_csv = null,

        // Images
        public readonly ?string $open_graph_image_file_name = null,
        public readonly ?string $open_graph_image_content_type = null,
        public readonly ?string $open_graph_image_file_size = null,

        // Integration
        public readonly ?int $rushb_id = null,
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
