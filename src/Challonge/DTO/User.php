<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Reflex\Challonge\DtoClientTrait;

/**
 * User DTO
 *
 * Represents a Challonge user account.
 * Properties are typed based on Challonge API v2.1 responses.
 */
class User
{
    use DtoClientTrait;

    public function __construct(
        // REQUIRED PARAMETERS - always present
        public readonly int $id,
        public readonly string $username,
        public readonly string $display_name,
        public readonly string $created_at,
        public readonly string $updated_at,
        public readonly string $challonge_url,
        
        // OPTIONAL WITH DEFAULTS
        public readonly bool $email_verified = false,
        public readonly int $tournaments_count = 0,
        public readonly int $communities_count = 0,
        
        // NULLABLE OPTIONAL PARAMETERS
        public readonly ?string $email = null,
        public readonly ?string $bio = null,
        public readonly ?string $location = null,
        public readonly ?string $website = null,
        public readonly ?string $avatar_url = null,
        public readonly ?string $banner_url = null,
        public readonly ?string $timezone = null,
        public readonly ?string $locale = null,
    ) {
    }
}
