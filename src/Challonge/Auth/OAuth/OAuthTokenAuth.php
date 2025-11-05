<?php

declare(strict_types=1);

namespace Reflex\Challonge\Auth\OAuth;

use Reflex\Challonge\Auth\AuthenticationInterface;

/**
 * OAuth 2.0 Token Authentication
 *
 * Use this for OAuth-authenticated requests to the Challonge API v2.1
 */
final class OAuthTokenAuth implements AuthenticationInterface
{
    public function __construct(
        private AccessToken $accessToken
    ) {
    }

    public function getAuthorizationType(): string
    {
        return 'v2';
    }

    public function getAuthorizationHeader(): string
    {
        return "{$this->accessToken->getTokenType()} {$this->accessToken->getAccessToken()}";
    }

    public function isValid(): bool
    {
        return !$this->accessToken->isExpired();
    }

    public function getAccessToken(): AccessToken
    {
        return $this->accessToken;
    }

    public function updateAccessToken(AccessToken $accessToken): void
    {
        $this->accessToken = $accessToken;
    }
}
