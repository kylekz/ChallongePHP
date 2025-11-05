<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Reflex\Challonge\DtoClientTrait;

/**
 * Match Attachment DTO
 *
 * Represents a file or image attachment associated with a match.
 * Required fields: url, description (per swagger)
 */
class Attachment
{
    use DtoClientTrait;

    public function __construct(
        // Core identifiers - always present
        public readonly int $id,

        // Required fields
        public readonly string $url,
        public readonly string $description,

        // Timestamps - always present
        public readonly string $created_at,
        public readonly string $updated_at,

        // Optional IDs
        public readonly ?int $match_id = null,
        public readonly ?int $user_id = null,

        // Optional file info
        public readonly ?string $original_file_name = null,
        public readonly ?string $asset_file_name = null,
        public readonly ?string $asset_content_type = null,
        public readonly ?int $asset_file_size = null,
        public readonly ?string $asset_url = null,
    ) {
    }

    /**
     * Update the attachment
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
        $response = $this->client->request('PUT', "matches/{$this->match_id}/attachments/{$this->id}", [
            'data' => [
                'type' => 'attachments',
                'id' => (string) $this->id,
                'attributes' => $attributes,
            ],
        ]);

        return self::fromResponse($this->client, $response['data'] ?? $response);
    }

    /**
     * Delete the attachment
     *
     * @throws \Reflex\Challonge\Exceptions\InvalidFormatException
     * @throws \Reflex\Challonge\Exceptions\NotFoundException
     * @throws \Reflex\Challonge\Exceptions\ServerException
     * @throws \Reflex\Challonge\Exceptions\UnauthorizedException
     * @throws \Reflex\Challonge\Exceptions\ValidationException
     */
    public function delete(): void
    {
        $this->client->request('DELETE', "matches/{$this->match_id}/attachments/{$this->id}");
    }
}
