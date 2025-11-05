<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Illuminate\Support\Collection;
use Reflex\Challonge\DtoClientTrait;
use Reflex\Challonge\Exceptions\AlreadyStartedException;
use Reflex\Challonge\Exceptions\StillRunningException;

/**
 * Tournament DTO
 *
 * Properties are typed based on Challonge API v2.1 swagger specification.
 * Core fields and fields with default values are non-nullable.
 * Optional fields that may not be present in responses are nullable.
 */
class Tournament
{
    use DtoClientTrait;

    public function __construct(
        // Core identifiers - always present
        public readonly int $id,
        public readonly string $name,
        public readonly string $tournament_type,
        public readonly string $state,

        // Timestamps - always present
        public readonly string $created_at,
        public readonly string $updated_at,

        // Core counts - have defaults
        public readonly int $participants_count = 0,
        public readonly int $progress_meter = 0,

        // Boolean settings - have defaults
        public readonly bool $private = false,
        public readonly bool $open_signup = false,
        public readonly bool $notify_users_when_matches_open = true,
        public readonly bool $notify_users_when_the_tournament_ends = true,
        public readonly bool $require_score_agreement = false,
        public readonly bool $accept_attachments = false,
        public readonly bool $hide_forum = false,
        public readonly bool $show_rounds = true,
        public readonly bool $sequential_pairings = false,
        public readonly bool $quick_advance = false,
        public readonly bool $hold_third_place_match = false,
        public readonly bool $hide_seeds = false,
        public readonly bool $participants_locked = false,
        public readonly bool $allow_participant_match_reporting = true,
        public readonly bool $split_participants = false,
        public readonly bool $group_stages_enabled = false,
        public readonly bool $created_by_api = false,
        public readonly bool $credit_capped = false,
        public readonly bool $review_before_finalizing = true,
        public readonly bool $accepting_predictions = false,
        public readonly bool $public_predictions_before_start_time = false,
        public readonly bool $group_stages_were_started = false,
        public readonly bool $participants_swappable = false,
        public readonly bool $team_convertable = false,

        // Integers with defaults
        public readonly int $swiss_rounds = 0,
        public readonly int $rr_iterations = 1,
        public readonly int $prediction_method = 0,
        public readonly int $max_predictions_per_user = 1,

        // Strings with defaults
        public readonly string $ranked_by = '',
        public readonly string $registration_type = 'free',

        // Optional strings
        public readonly ?string $url = null,
        public readonly ?string $description = null,
        public readonly ?string $description_source = null,
        public readonly ?string $game_name = null,
        public readonly ?string $subdomain = null,
        public readonly ?string $full_challonge_url = null,
        public readonly ?string $live_image_url = null,
        public readonly ?string $sign_up_url = null,
        public readonly ?string $grand_finals_modifier = null,
        public readonly ?string $registration_fee = null,

        // Optional timestamps
        public readonly ?string $start_at = null,
        public readonly ?string $started_at = null,
        public readonly ?string $completed_at = null,
        public readonly ?string $started_checking_in_at = null,
        public readonly ?string $locked_at = null,
        public readonly ?string $predictions_opened_at = null,

        // Optional integers
        public readonly ?int $signup_cap = null,
        public readonly ?int $category = null,
        public readonly ?int $game_id = null,
        public readonly ?int $event_id = null,
        public readonly ?int $program_id = null,
        public readonly ?int $tournament_registration_id = null,

        // Optional booleans
        public readonly ?bool $show_participant_country = null,
        public readonly ?bool $donation_contest_enabled = null,
        public readonly ?bool $mandatory_donation = null,
        public readonly ?bool $auto_assign_stations = null,
        public readonly ?bool $only_start_matches_with_stations = null,
        public readonly ?bool $anonymous_voting = null,
        public readonly ?bool $use_new_style = null,

        // Points/Scoring - strings with defaults
        public readonly string $pts_for_game_win = '0.0',
        public readonly string $pts_for_game_tie = '0.0',
        public readonly string $pts_for_match_win = '1.0',
        public readonly string $pts_for_match_tie = '0.5',
        public readonly string $pts_for_bye = '1.0',
        public readonly string $rr_pts_for_game_win = '0.0',
        public readonly string $rr_pts_for_game_tie = '0.0',
        public readonly string $rr_pts_for_match_win = '1.0',
        public readonly string $rr_pts_for_match_tie = '0.5',

        // Mixed/array types
        public readonly mixed $teams = null,
        public readonly mixed $check_in_duration = null,
        public readonly mixed $tie_breaks = null,
        public readonly mixed $ranked = null,
        public readonly mixed $spam = null,
        public readonly mixed $ham = null,
        public readonly mixed $toxic = null,
        public readonly mixed $predict_the_losers_bracket = null,
        public readonly mixed $program_classification_ids_allowed = null,
        public readonly mixed $non_elimination_tournament_data = null,
        public readonly ?string $team_size_range = null,
        public readonly ?array $allowed_regions = null,
        public readonly array $optional_display_data = [],
    ) {
    }

    /**
     * Start a tournament, opening up first round matches for score reporting
     *
     * @throws AlreadyStartedException
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function start(): self
    {
        if ($this->state === 'underway' || $this->state === 'in_progress') {
            throw new AlreadyStartedException('Tournament is already underway.');
        }

        $response = $this->client->request('POST', "tournaments/{$this->id}/change_state", [
            'data' => [
                'type' => 'tournaments',
                'id' => (string) $this->id,
                'attributes' => [
                    'state' => 'start',
                ],
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Finalize a tournament that has had all match scores submitted
     *
     * @throws StillRunningException
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function finalize(): self
    {
        if ($this->state !== 'awaiting_review') {
            throw new StillRunningException('Tournament is still running.');
        }

        $response = $this->client->request('POST', "tournaments/{$this->id}/change_state", [
            'data' => [
                'type' => 'tournaments',
                'id' => (string) $this->id,
                'attributes' => [
                    'state' => 'finalize',
                ],
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Reset a tournament, clearing all of its scores and attachments
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function reset(): self
    {
        $response = $this->client->request('POST', "tournaments/{$this->id}/change_state", [
            'data' => [
                'type' => 'tournaments',
                'id' => (string) $this->id,
                'attributes' => [
                    'state' => 'reset',
                ],
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Update a tournament's attributes
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
        $response = $this->client->request('PUT', "tournaments/{$this->id}", [
            'data' => [
                'type' => 'tournaments',
                'id' => (string) $this->id,
                'attributes' => $attributes,
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Delete a tournament along with all its associated records
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function delete(): void
    {
        $this->client->request('DELETE', "tournaments/{$this->id}");
    }

    /**
     * Remove all participants
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function clear(): self
    {
        $response = $this->client->request('POST', "tournaments/{$this->id}/participants/clear");

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Process all check-ins before the tournament has started
     *
     * @throws AlreadyStartedException
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function processCheckins(): self
    {
        if ($this->state === 'underway' || $this->state === 'in_progress') {
            throw new AlreadyStartedException('Tournament is already underway.');
        }

        $response = $this->client->request('POST', "tournaments/{$this->id}/process_check_ins");

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Cancel all check-ins before the tournament has started
     *
     * @throws AlreadyStartedException
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function abortCheckins(): self
    {
        if ($this->state === 'underway' || $this->state === 'in_progress') {
            throw new AlreadyStartedException('Tournament is already underway.');
        }

        $response = $this->client->request('POST', "tournaments/{$this->id}/abort_check_in");

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Add a participant to a tournament (up until it is started)
     *
     * @param array<string, mixed> $attributes
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function addParticipant(array $attributes = []): Participant
    {
        $response = $this->client->request('POST', "tournaments/{$this->id}/participants", [
            'data' => [
                'type' => 'participants',
                'attributes' => $attributes,
            ],
        ]);

        return Participant::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Bulk add participants to a tournament (up until it is started)
     *
     * @param array<array<string, mixed>> $participants
     * @return Collection<int, Participant>
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function bulkAddParticipant(array $participants): Collection
    {
        $response = $this->client->request('POST', "tournaments/{$this->id}/participants/bulk_add", [
            'data' => array_map(fn (array $p) => [
                'type' => 'participants',
                'attributes' => $p,
            ], $participants),
        ]);

        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $participant) => Participant::fromResponse($this->client, $participant));
    }

    /**
     * Delete a participant (before tournament starts)
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function deleteParticipant(int $id): void
    {
        $this->client->request('DELETE', "tournaments/{$this->id}/participants/{$id}");
    }

    /**
     * Update a tournament participant's attributes
     *
     * @param array<string, mixed> $attributes
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function updateParticipant(int $id, array $attributes = []): Participant
    {
        $response = $this->client->request('PUT', "tournaments/{$this->id}/participants/{$id}", [
            'data' => [
                'type' => 'participants',
                'id' => (string) $id,
                'attributes' => $attributes,
            ],
        ]);

        return Participant::fromResponse($this->client, $response['data'] ?? $response);
    }
}
