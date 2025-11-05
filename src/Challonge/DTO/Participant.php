<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Reflex\Challonge\DtoClientTrait;

/**
 * Participant DTO
 *
 * Properties are typed based on Challonge API v2.1 swagger specification.
 * Required: name
 */
class Participant
{
    use DtoClientTrait;

    public function __construct(
        // Core identifiers - always present
        public readonly int $id,
        public readonly int $tournament_id,

        // Required fields
        public readonly string $name,
        public readonly string $display_name,

        // Seeding - has default
        public readonly int $seed = 1,

        // Boolean status fields - have defaults
        public readonly bool $active = true,
        public readonly bool $on_waiting_list = false,
        public readonly bool $removable = true,
        public readonly bool $reactivatable = false,
        public readonly bool $confirm_remove = false,
        public readonly bool $participatable_or_invitation_attached = false,
        public readonly bool $invitation_pending = false,
        public readonly bool $check_in_open = false,
        public readonly bool $can_check_in = false,
        public readonly bool $checked_in = false,
        public readonly bool $has_irrelevant_seed = false,

        // Timestamps - always present
        public readonly string $created_at,
        public readonly string $updated_at,

        // Arrays - have defaults
        public readonly array $group_player_ids = [],

        // Optional display
        public readonly string $display_name_with_invitation_email_address = '',

        // Optional strings
        public readonly ?string $username = null,
        public readonly ?string $challonge_username = null,
        public readonly ?string $invite_email = null,
        public readonly ?string $challonge_email_address_verified = null,
        public readonly ?string $email_hash = null,
        public readonly ?string $misc = null,
        public readonly ?string $icon = null,
        public readonly ?string $attached_participatable_portrait_url = null,

        // Optional integers
        public readonly ?int $group_id = null,
        public readonly ?int $final_rank = null,
        public readonly ?int $invitation_id = null,

        // Optional timestamp
        public readonly ?string $checked_in_at = null,

        // Mixed types
        public readonly mixed $clinch = null,
        public readonly mixed $ranked_member_id = null,
        public readonly mixed $custom_field_response = null,
        public readonly ?array $integration_uids = null,
    ) {
    }

    /**
     * Update the participant's attributes
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
        $response = $this->client->request('PUT', "tournaments/{$this->tournament_id}/participants/{$this->id}", [
            'data' => [
                'type' => 'participants',
                'id' => (string) $this->id,
                'attributes' => $attributes,
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Delete the participant (before tournament starts)
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function delete(): void
    {
        $this->client->request('DELETE', "tournaments/{$this->tournament_id}/participants/{$this->id}");
    }

    /**
     * Check the participant in
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function checkin(): self
    {
        $response = $this->client->request('POST', "tournaments/{$this->tournament_id}/participants/{$this->id}/check_in");

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Undo a participant check-in
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function undoCheckin(): self
    {
        $response = $this->client->request('POST', "tournaments/{$this->tournament_id}/participants/{$this->id}/undo_check_in");

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }
}
