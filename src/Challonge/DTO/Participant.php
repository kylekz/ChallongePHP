<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Reflex\Challonge\DtoClientTrait;

/**
 * Participant DTO
 *
 * Note: All properties are nullable because Challonge's API is not stable
 * and frequently adds/changes fields. The v2.1 API uses JSON API format.
 */
class Participant
{
    use DtoClientTrait;

    public function __construct(
        // Core identifiers
        public readonly ?int $id = null,
        public readonly ?int $tournament_id = null,

        // Basic info
        public readonly ?string $name = null,
        public readonly ?string $display_name = null,
        public readonly ?string $display_name_with_invitation_email_address = null,
        public readonly ?string $username = null,
        public readonly ?string $challonge_username = null,

        // Email
        public readonly ?string $invite_email = null,
        public readonly ?string $challonge_email_address_verified = null,
        public readonly ?string $email_hash = null,

        // Status
        public readonly ?bool $active = null,
        public readonly ?bool $on_waiting_list = null,
        public readonly ?bool $removable = null,
        public readonly ?bool $reactivatable = null,
        public readonly ?bool $confirm_remove = null,
        public readonly ?bool $participatable_or_invitation_attached = null,
        public readonly ?bool $invitation_pending = null,

        // Check-in
        public readonly ?bool $check_in_open = null,
        public readonly ?bool $can_check_in = null,
        public readonly ?bool $checked_in = null,
        public readonly ?string $checked_in_at = null,

        // Seeding and ranking
        public readonly ?int $seed = null,
        public readonly ?int $final_rank = null,
        public readonly ?bool $has_irrelevant_seed = null,
        public readonly mixed $clinch = null,

        // Group/Team
        public readonly ?int $group_id = null,
        public readonly ?array $group_player_ids = null,

        // Integration
        public readonly ?array $integration_uids = null,
        public readonly ?int $invitation_id = null,
        public readonly mixed $ranked_member_id = null,

        // Media
        public readonly ?string $icon = null,
        public readonly ?string $attached_participatable_portrait_url = null,

        // Custom fields
        public readonly mixed $custom_field_response = null,
        public readonly ?string $misc = null,

        // Timestamps
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
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
