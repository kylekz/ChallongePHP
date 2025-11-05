<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Illuminate\Support\Collection;
use Reflex\Challonge\DtoClientTrait;

/**
 * Round DTO
 *
 * Represents a round within a race tournament.
 */
class Round
{
    use DtoClientTrait;

    public function __construct(
        // Core identifiers
        public readonly ?int $id = null,
        public readonly ?int $race_id = null,

        // Round info
        public readonly ?int $number = null,
        public readonly ?string $state = null,
        public readonly ?string $name = null,

        // Timestamps
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
        public readonly ?string $started_at = null,
        public readonly ?string $completed_at = null,

        // Metadata
        public readonly ?int $elapsed_times_count = null,
    ) {
    }

    /**
     * Update the round
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
        $response = $this->client->request('PUT', "races/{$this->race_id}/rounds/{$this->id}", [
            'data' => [
                'type' => 'rounds',
                'id' => (string) $this->id,
                'attributes' => $attributes,
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Delete the round
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function delete(): void
    {
        $this->client->request('DELETE', "races/{$this->race_id}/rounds/{$this->id}");
    }

    /**
     * Get all elapsed times for this round
     *
     * @return Collection<int, ElapsedTime>
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function getElapsedTimes(): Collection
    {
        $response = $this->client->request('GET', "races/{$this->race_id}/rounds/{$this->id}/elapsed_times");
        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $time) => ElapsedTime::fromResponse($this->client, $time));
    }

    /**
     * Create an elapsed time entry
     *
     * @param array<string, mixed> $attributes
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function createElapsedTime(array $attributes = []): ElapsedTime
    {
        $response = $this->client->request('POST', "races/{$this->race_id}/rounds/{$this->id}/elapsed_times", [
            'data' => [
                'type' => 'elapsed_times',
                'attributes' => $attributes,
            ],
        ]);

        return ElapsedTime::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Bulk update elapsed times
     *
     * @param array<array<string, mixed>> $times
     * @return Collection<int, ElapsedTime>
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function bulkUpdateElapsedTimes(array $times): Collection
    {
        $response = $this->client->request('POST', "races/{$this->race_id}/rounds/{$this->id}/elapsed_times/bulk_update", [
            'data' => array_map(fn (array $t) => [
                'type' => 'elapsed_times',
                'attributes' => $t,
            ], $times),
        ]);

        $data = $response['data'] ?? $response;

        return Collection::make($data)
            ->map(fn (array $time) => ElapsedTime::fromResponse($this->client, $time));
    }
}
