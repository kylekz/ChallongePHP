<?php

declare(strict_types=1);

namespace Reflex\Challonge\Auth;

/**
 * API v1 Key Authentication
 *
 * For backwards compatibility and simple use cases where you only need
 * authorization on behalf of your own account.
 *
 * Get your API key from: https://challonge.com/settings/developer
 */
final class ApiKeyAuth implements AuthenticationInterface
{
    public function __construct(
        private readonly string $apiKey
    ) {
    }

    public function getAuthorizationType(): string
    {
        return 'v1';
    }

    public function getAuthorizationHeader(): string
    {
        return $this->apiKey;
    }

    public function isValid(): bool
    {
        return !empty($this->apiKey);
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }
}
