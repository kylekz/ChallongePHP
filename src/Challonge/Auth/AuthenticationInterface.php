<?php

declare(strict_types=1);

namespace Reflex\Challonge\Auth;

/**
 * Interface for authentication providers
 */
interface AuthenticationInterface
{
    /**
     * Get the authorization type (v1 or v2)
     */
    public function getAuthorizationType(): string;

    /**
     * Get the authorization header value
     */
    public function getAuthorizationHeader(): string;

    /**
     * Check if the authentication is valid/not expired
     */
    public function isValid(): bool;
}
