<?php

declare(strict_types=1);

namespace Reflex\Challonge\DTO;

use Reflex\Challonge\DtoClientTrait;

/**
 * Community DTO
 *
 * Represents a Challonge community.
 * Properties are typed based on Challonge API v2.1 responses.
 */
class Community
{
    use DtoClientTrait;

    public function __construct(
        // REQUIRED PARAMETERS - always present
        public readonly int $id,
        public readonly string $name,
        public readonly string $url,
        public readonly string $identifier,
        public readonly string $created_at,
        public readonly string $updated_at,
        public readonly string $full_challonge_url,
        
        // OPTIONAL WITH DEFAULTS
        public readonly bool $private = false,
        public readonly bool $hide_global_chat = false,
        public readonly int $member_count = 0,
        public readonly int $tournaments_count = 0,
        
        // NULLABLE OPTIONAL PARAMETERS
        public readonly ?string $description = null,
        public readonly ?string $description_source = null,
        public readonly ?string $banner_url = null,
        public readonly ?string $icon_url = null,
    ) {
    }
}
