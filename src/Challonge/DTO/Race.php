<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Illuminate\Support\Collection;
use Reflex\Challonge\DtoClientTrait;

/**
 * Race DTO
 *
 * Represents a racing-style tournament where timing matters.
 */
class Race
{
    use DtoClientTrait;

    public function __construct(
        // Core identifiers
        public readonly ?int $id = null,
        public readonly ?string $name = null,
        public readonly ?string $url = null,

        // Race configuration
        public readonly ?string $state = null,
        public readonly ?string $description = null,
        public readonly ?string $race_type = null,

        // Timing
        public readonly ?int $target_round_count = null,
        public readonly ?int $current_round = null,

        // Participants
        public readonly ?int $participants_count = null,
        public readonly ?int $signup_cap = null,

        // Settings
        public readonly ?bool $private = null,
        public readonly ?bool $notify_users_when_matches_open = null,
        public readonly ?bool $notify_users_when_the_tournament_ends = null,

        // Timestamps
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
        public readonly ?string $start_at = null,
        public readonly ?string $started_at = null,
        public readonly ?string $completed_at = null,

        // URLs
        public readonly ?string $full_challonge_url = null,
        public readonly ?string $live_image_url = null,
        public readonly ?string $sign_up_url = null,

        // Metadata
        public readonly ?bool $created_by_api = null,
        public readonly ?int $game_id = null,
        public readonly ?string $game_name = null,
    ) {
    }

    /**
     * Update the race
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
        $response = $this->client->request('PUT', "races/{$this->id}", [
            'data' => [
                'type' => 'races',
                'id' => (string) $this->id,
                'attributes' => $attributes,
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Change race state (start, finalize, etc.)
     *
     * @param string $action Action: 'start', 'finalize', 'reset'
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function changeState(string $action): self
    {
        $response = $this->client->request('POST', "races/{$this->id}/change_state", [
            'data' => [
                'type' => 'races',
                'id' => (string) $this->id,
                'attributes' => [
                    'state' => $action,
                ],
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Delete the race
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function delete(): void
    {
        $this->client->request('DELETE', "races/{$this->id}");
    }

    /**
     * Get all rounds for this race
     *
     * @return Collection<int, Round>
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function getRounds(): Collection
    {
        $response = $this->client->request('GET', "races/{$this->id}/rounds");
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $round) => Round::fromResponse($this->client, $round));
    }

    /**
     * Create a new round
     *
     * @param array<string, mixed> $attributes
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function createRound(array $attributes = []): Round
    {
        $response = $this->client->request('POST', "races/{$this->id}/rounds", [
            'data' => [
                'type' => 'rounds',
                'attributes' => $attributes,
            ],
        ]);

        return Round::fromResponse($this->client, $response['data'] ?? $response);
    }
}
