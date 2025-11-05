<?php

declare(strict_types=1);

namespace Reflex\Challonge\Auth\OAuth;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

/**
 * Device Authorization Grant Flow
 *
 * For games or apps on platforms without web browsers or keyboards; gain authorization
 * from Challonge users to make requests on their behalf.
 *
 * Examples: Get a player's Challonge ID to validate their inclusion in a tournament,
 * check a player in to a tournament, filter your in-game tournament list.
 */
final class DeviceAuthorizationFlow
{
    public function __construct(
        private readonly OAuthConfig $config,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    /**
     * Request a device code
     *
     * @return array{device_code: string, user_code: string, verification_uri: string, expires_in: int, interval: int}
     * @throws RuntimeException
     */
    public function requestDeviceCode(): array
    {
        $params = [
            'client_id' => $this->config->getClientId(),
            'scope' => $this->config->getScopeString(),
        ];

        $request = $this->requestFactory
            ->createRequest('POST', $this->config->getDeviceCodeUrl())
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->streamFactory->createStream(http_build_query($params)));

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                "Device code request failed with status {$response->getStatusCode()}: {$response->getBody()}"
            );
        }

        $data = json_decode((string) $response->getBody(), true);

        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON response from OAuth server');
        }

        return $data;
    }

    /**
     * Poll for access token
     *
     * Keep calling this method at the interval specified in the device code response
     * until it returns an AccessToken or throws an exception.
     *
     * @throws RuntimeException If polling fails (not authorization_pending)
     */
    public function pollForToken(string $deviceCode): ?AccessToken
    {
        $params = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'device_code' => $deviceCode,
            'client_id' => $this->config->getClientId(),
        ];

        $request = $this->requestFactory
            ->createRequest('POST', $this->config->getTokenUrl())
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->streamFactory->createStream(http_build_query($params)));

        $response = $this->httpClient->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $data = json_decode((string) $response->getBody(), true);

            if (!is_array($data)) {
                throw new RuntimeException('Invalid JSON response from OAuth server');
            }

            return AccessToken::fromArray($data);
        }

        $body = (string) $response->getBody();
        $data = json_decode($body, true);

        // Check if we're still waiting for authorization
        if (is_array($data) && isset($data['error']) && $data['error'] === 'authorization_pending') {
            return null;
        }

        throw new RuntimeException(
            "Device token poll failed with status {$response->getStatusCode()}: {$body}"
        );
    }
}
