<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Reflex\Challonge\DtoClientTrait;

/**
 * User DTO
 *
 * Represents the authenticated user's profile information.
 */
class User
{
    use DtoClientTrait;

    public function __construct(
        // Core identifiers
        public readonly ?int $id = null,
        public readonly ?string $username = null,
        public readonly ?string $display_name = null,
        public readonly ?string $email = null,

        // Profile
        public readonly ?string $bio = null,
        public readonly ?string $location = null,
        public readonly ?string $website = null,

        // Images
        public readonly ?string $avatar_url = null,
        public readonly ?string $banner_url = null,

        // Verification
        public readonly ?bool $email_verified = null,

        // Preferences
        public readonly ?string $timezone = null,
        public readonly ?string $locale = null,

        // Metadata
        public readonly ?int $tournaments_count = null,
        public readonly ?int $communities_count = null,

        // Timestamps
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null,

        // URLs
        public readonly ?string $challonge_url = null,
    ) {
    }
}
