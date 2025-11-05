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
 * Note: All properties are nullable because Challonge's API is not stable
 * and frequently adds/changes fields. The v2.1 API uses JSON API format.
 */
class Tournament
{
    use DtoClientTrait;

    public function __construct(
        // Core identifiers
        public readonly ?int $id = null,
        public readonly ?string $name = null,
        public readonly ?string $url = null,

        // Tournament configuration
        public readonly ?string $tournament_type = null,
        public readonly ?string $state = null,
        public readonly ?string $description = null,
        public readonly ?string $description_source = null,

        // Dates
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
        public readonly ?string $start_at = null,
        public readonly ?string $started_at = null,
        public readonly ?string $completed_at = null,
        public readonly ?string $started_checking_in_at = null,
        public readonly ?string $locked_at = null,
        public readonly ?string $predictions_opened_at = null,

        // Tournament settings
        public readonly ?bool $open_signup = null,
        public readonly ?bool $private = null,
        public readonly ?bool $notify_users_when_matches_open = null,
        public readonly ?bool $notify_users_when_the_tournament_ends = null,
        public readonly ?bool $require_score_agreement = null,
        public readonly ?bool $accept_attachments = null,
        public readonly ?bool $hide_forum = null,
        public readonly ?bool $show_rounds = null,
        public readonly ?bool $sequential_pairings = null,

        // Bracket/Match settings
        public readonly ?bool $quick_advance = null,
        public readonly ?bool $hold_third_place_match = null,
        public readonly ?bool $hide_seeds = null,
        public readonly ?string $ranked_by = null,
        public readonly ?string $grand_finals_modifier = null,

        // Points/Scoring
        public readonly ?string $pts_for_game_win = null,
        public readonly ?string $pts_for_game_tie = null,
        public readonly ?string $pts_for_match_win = null,
        public readonly ?string $pts_for_match_tie = null,
        public readonly ?string $pts_for_bye = null,
        public readonly ?string $rr_pts_for_game_win = null,
        public readonly ?string $rr_pts_for_game_tie = null,
        public readonly ?string $rr_pts_for_match_win = null,
        public readonly ?string $rr_pts_for_match_tie = null,

        // Swiss/Round Robin
        public readonly ?int $swiss_rounds = null,
        public readonly ?int $rr_iterations = null,

        // Participants
        public readonly ?int $participants_count = null,
        public readonly ?int $signup_cap = null,
        public readonly ?bool $participants_locked = null,
        public readonly ?bool $participants_swappable = null,
        public readonly ?bool $allow_participant_match_reporting = null,
        public readonly ?bool $split_participants = null,
        public readonly ?bool $show_participant_country = null,

        // Teams
        public readonly mixed $teams = null,
        public readonly ?bool $team_convertable = null,
        public readonly ?string $team_size_range = null,

        // Check-in
        public readonly mixed $check_in_duration = null,

        // Progress/Status
        public readonly ?int $progress_meter = null,
        public readonly ?bool $review_before_finalizing = null,

        // Predictions
        public readonly ?int $prediction_method = null,
        public readonly ?bool $anonymous_voting = null,
        public readonly ?int $max_predictions_per_user = null,
        public readonly ?bool $accepting_predictions = null,
        public readonly ?bool $public_predictions_before_start_time = null,
        public readonly mixed $predict_the_losers_bracket = null,

        // Group stages
        public readonly ?bool $group_stages_enabled = null,
        public readonly ?bool $group_stages_were_started = null,

        // API/Meta
        public readonly ?bool $created_by_api = null,
        public readonly ?bool $credit_capped = null,
        public readonly mixed $tie_breaks = null,
        public readonly mixed $ranked = null,
        public readonly mixed $spam = null,
        public readonly mixed $ham = null,
        public readonly mixed $toxic = null,
        public readonly ?bool $use_new_style = null,

        // Registration
        public readonly ?string $registration_fee = null,
        public readonly ?string $registration_type = null,
        public readonly ?int $tournament_registration_id = null,
        public readonly ?bool $donation_contest_enabled = null,
        public readonly ?bool $mandatory_donation = null,

        // Stations
        public readonly ?bool $auto_assign_stations = null,
        public readonly ?bool $only_start_matches_with_stations = null,

        // Categories/Games
        public readonly ?int $category = null,
        public readonly ?int $game_id = null,
        public readonly ?string $game_name = null,
        public readonly ?int $event_id = null,
        public readonly ?int $program_id = null,
        public readonly mixed $program_classification_ids_allowed = null,
        public readonly mixed $non_elimination_tournament_data = null,

        // URLs
        public readonly ?string $subdomain = null,
        public readonly ?string $full_challonge_url = null,
        public readonly ?string $live_image_url = null,
        public readonly ?string $sign_up_url = null,

        // Regions
        public readonly ?array $allowed_regions = null,

        // Display
        public readonly ?array $optional_display_data = null,
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
