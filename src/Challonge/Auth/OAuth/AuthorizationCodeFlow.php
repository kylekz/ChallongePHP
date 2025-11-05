<?php

declare(strict_types=1);

namespace Reflex\Challonge\Auth\OAuth;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

/**
 * OAuth Authorization Code Flow
 *
 * Use case: For websites or apps on platforms with web browsers - gain authorization
 * from Challonge users to make requests on their behalf. This is akin to signing in
 * to a service with Facebook or Discord.
 *
 * Access tokens have a 1-week expiration, but refresh tokens are supported.
 */
final class AuthorizationCodeFlow
{
    public function __construct(
        private readonly OAuthConfig $config,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * Get the authorization URL to redirect users to
     *
     * @param array<string, string> $additionalParams Additional query parameters (e.g., state)
     */
    public function getAuthorizationUrl(array $additionalParams = []): string
    {
        $params = array_merge([
            'client_id' => $this->config->getClientId(),
            'redirect_uri' => $this->config->getRedirectUri(),
            'response_type' => 'code',
            'scope' => $this->config->getScopeString(),
        ], $additionalParams);

        return $this->config->getAuthorizeUrl() . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     *
     * @throws RuntimeException If token exchange fails
     */
    public function exchangeCodeForToken(string $code): AccessToken
    {
        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->config->getRedirectUri(),
            'client_id' => $this->config->getClientId(),
            'client_secret' => $this->config->getClientSecret(),
        ];

        return $this->requestToken($params);
    }

    /**
     * Refresh an expired access token
     *
     * @throws RuntimeException If token refresh fails
     */
    public function refreshToken(string $refreshToken): AccessToken
    {
        $params = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->config->getClientId(),
            'client_secret' => $this->config->getClientSecret(),
        ];

        return $this->requestToken($params);
    }

    /**
     * Request a token from the OAuth server
     *
     * @param array<string, string> $params
     * @throws RuntimeException
     */
    private function requestToken(array $params): AccessToken
    {
        $request = $this->requestFactory
            ->createRequest('POST', $this->config->getTokenUrl())
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->streamFactory->createStream(http_build_query($params)));

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                "OAuth token request failed with status {$response->getStatusCode()}: {$response->getBody()}"
            );
        }

        $data = json_decode((string) $response->getBody(), true);

        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON response from OAuth server');
        }

        return AccessToken::fromArray($data);
    }
}
