<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Illuminate\Support\Collection;
use Reflex\Challonge\DtoClientTrait;

/**
 * Community DTO
 *
 * Represents a Challonge community that can host tournaments.
 */
class Community
{
    use DtoClientTrait;

    public function __construct(
        // Core identifiers
        public readonly ?int $id = null,
        public readonly ?string $name = null,
        public readonly ?string $url = null,
        public readonly ?string $identifier = null,

        // Description
        public readonly ?string $description = null,
        public readonly ?string $description_source = null,

        // Settings
        public readonly ?bool $private = null,
        public readonly ?bool $hide_global_chat = null,

        // Metadata
        public readonly ?int $member_count = null,
        public readonly ?int $tournaments_count = null,

        // Images
        public readonly ?string $banner_url = null,
        public readonly ?string $icon_url = null,

        // Timestamps
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,

        // URLs
        public readonly ?string $full_challonge_url = null,
    ) {
    }

    /**
     * Get all tournaments in this community
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, Tournament>
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function getTournaments(array $filters = []): Collection
    {
        $response = $this->client->request('GET', "communities/{$this->identifier}/tournaments", [], $filters);
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $tournament) => Tournament::fromResponse($this->client, $tournament));
    }

    /**
     * Create a tournament in this community
     *
     * @param array<string, mixed> $attributes
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function createTournament(array $attributes = []): Tournament
    {
        $response = $this->client->request('POST', "communities/{$this->identifier}/tournaments", [
            'data' => [
                'type' => 'tournaments',
                'attributes' => $attributes,
            ],
        ]);

        return Tournament::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Get a specific tournament in this community
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function getTournament(string $tournamentId): Tournament
    {
        $response = $this->client->request('GET', "communities/{$this->identifier}/tournaments/{$tournamentId}");

        return Tournament::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Get participants for a community tournament
     *
     * @return Collection<int, Participant>
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function getTournamentParticipants(string $tournamentId): Collection
    {
        $response = $this->client->request('GET', "communities/{$this->identifier}/tournaments/{$tournamentId}/participants");
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $participant) => Participant::fromResponse($this->client, $participant));
    }

    /**
     * Get matches for a community tournament
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, MatchDto>
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function getTournamentMatches(string $tournamentId, array $filters = []): Collection
    {
        $response = $this->client->request('GET', "communities/{$this->identifier}/tournaments/{$tournamentId}/matches", [], $filters);
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $match) => MatchDto::fromResponse($this->client, $match));
    }

    /**
     * Get match attachments for a community tournament
     *
     * @return Collection<int, Attachment>
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function getTournamentMatchAttachments(string $tournamentId): Collection
    {
        $response = $this->client->request('GET', "communities/{$this->identifier}/tournaments/{$tournamentId}/match_attachments");
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $attachment) => Attachment::fromResponse($this->client, $attachment));
    }
}
