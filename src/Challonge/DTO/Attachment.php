<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Reflex\Challonge\DtoClientTrait;

/**
 * Match Attachment DTO
 *
 * Represents a file or image attachment associated with a match.
 */
class Attachment
{
    use DtoClientTrait;

    public function __construct(
        // Core identifiers
        public readonly ?int $id = null,
        public readonly ?int $match_id = null,
        public readonly ?int $user_id = null,

        // Attachment info
        public readonly ?string $description = null,
        public readonly ?string $url = null,
        public readonly ?string $original_file_name = null,

        // Timestamps
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,

        // Asset details
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
