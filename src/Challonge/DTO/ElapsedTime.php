<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Reflex\Challonge\DtoClientTrait;

/**
 * Elapsed Time DTO
 *
 * Represents a participant's elapsed time in a race round.
 */
class ElapsedTime
{
    use DtoClientTrait;

    public function __construct(
        // Core identifiers
        public readonly ?int $id = null,
        public readonly ?int $round_id = null,
        public readonly ?int $participant_id = null,

        // Time data
        public readonly ?float $elapsed_time_millis = null,
        public readonly ?float $elapsed_time_seconds = null,
        public readonly ?string $display_time = null,

        // Status
        public readonly ?int $rank = null,
        public readonly ?bool $disqualified = null,
        public readonly ?bool $dnf = null, // Did Not Finish
        public readonly ?bool $dns = null, // Did Not Start

        // Timestamps
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,
        public readonly ?string $started_at = null,
        public readonly ?string $finished_at = null,
    ) {
    }

    /**
     * Update the elapsed time
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
        $response = $this->client->request('PUT', "rounds/{$this->round_id}/elapsed_times/{$this->id}", [
            'data' => [
                'type' => 'elapsed_times',
                'id' => (string) $this->id,
                'attributes' => $attributes,
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Delete the elapsed time entry
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function delete(): void
    {
        $this->client->request('DELETE', "rounds/{$this->round_id}/elapsed_times/{$this->id}");
    }
}
