<?php

declare(strict_types=1);

namespace Reflex\Challonge\Auth\OAuth;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

/**
 * Client Credentials Flow
 *
 * Use case: From web servers, game servers, and other devices you control, make requests
 * on behalf of your Challonge developer application.
 *
 * Examples: Fetch all tournaments created by users in my application (created via the
 * Authorization Code flow), publish validated match results from my game.
 *
 * This flow is used to access application-scoped API routes (e.g., /application/tournaments.json).
 */
final class ClientCredentialsFlow
{
    public function __construct(
        private readonly OAuthConfig $config,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * Get an access token using client credentials
     *
     * @throws RuntimeException If token request fails
     */
    public function getAccessToken(): AccessToken
    {
        $params = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config->getClientId(),
            'client_secret' => $this->config->getClientSecret(),
            'scope' => $this->config->getScopeString(),
        ];

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
