<?php

declare(strict_types=1);

namespace Reflex\Challonge\Auth\OAuth;

/**
 * OAuth configuration for Challonge applications
 */
final class OAuthConfig
{
    private const AUTHORIZE_URL = 'https://api.challonge.com/oauth/authorize';
    private const TOKEN_URL = 'https://api.challonge.com/oauth/token';
    private const DEVICE_CODE_URL = 'https://api.challonge.com/oauth/device/code';

    /**
     * @param string $clientId Your Challonge application client ID
     * @param string $clientSecret Your Challonge application client secret
     * @param string $redirectUri Redirect URI registered with your application
     * @param array<string> $scopes OAuth scopes to request
     */
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri = '',
        private readonly array $scopes = [],
    ) {
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    /**
     * @return array<string>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function getScopeString(): string
    {
        return implode(' ', $this->scopes);
    }

    public function getAuthorizeUrl(): string
    {
        return self::AUTHORIZE_URL;
    }

    public function getTokenUrl(): string
    {
        return self::TOKEN_URL;
    }

    public function getDeviceCodeUrl(): string
    {
        return self::DEVICE_CODE_URL;
    }

    /**
     * Available OAuth scopes
     */
    public const SCOPE_ME = 'me';
    public const SCOPE_APPLICATION_ORGANIZER = 'application:organizer';
    public const SCOPE_APPLICATION_PLAYER = 'application:player';
    public const SCOPE_TOURNAMENTS_READ = 'tournaments:read';
    public const SCOPE_TOURNAMENTS_WRITE = 'tournaments:write';
    public const SCOPE_MATCHES_READ = 'matches:read';
    public const SCOPE_MATCHES_WRITE = 'matches:write';
    public const SCOPE_ATTACHMENTS_READ = 'attachments:read';
    public const SCOPE_ATTACHMENTS_WRITE = 'attachments:write';
    public const SCOPE_PARTICIPANTS_READ = 'participants:read';
    public const SCOPE_PARTICIPANTS_WRITE = 'participants:write';
    public const SCOPE_COMMUNITIES_MANAGE = 'communities:manage';
}
