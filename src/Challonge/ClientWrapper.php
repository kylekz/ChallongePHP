<?php

declare(strict_types=1);

namespace Reflex\Challonge;

use JsonException;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Reflex\Challonge\Auth\AuthenticationInterface;
use Reflex\Challonge\Exceptions\InvalidFormatException;
use Reflex\Challonge\Exceptions\NotFoundException;
use Reflex\Challonge\Exceptions\ServerException;
use Reflex\Challonge\Exceptions\UnauthorizedException;
use Reflex\Challonge\Exceptions\UnexpectedErrorException;
use Reflex\Challonge\Exceptions\ValidationException;

/**
 * HTTP Client wrapper for Challonge API v2.1
 *
 * Supports both v1 API keys and OAuth v2 authentication
 */
class ClientWrapper
{
    private const API_BASE_URL = 'https://api.challonge.com';
    private const API_VERSION = 'v2.1';

    public function __construct(
        protected ClientInterface $client,
        protected AuthenticationInterface $auth,
        protected string $version = '6.0.0',
        protected bool $mapOptions = false, // v2.1 uses JSON API format, no mapping needed by default
    ) {
    }

    /**
     * Make a request to Challonge via the HTTP client
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $uri API endpoint (e.g., 'tournaments' or 'tournaments/123')
     * @param array<string, mixed> $content Request body data
     * @param array<string, string> $queryParams Query parameters
     * @return array<string, mixed> Response data
     *
     * @throws InvalidFormatException
     * @throws JsonException
     * @throws NotFoundException
     * @throws ServerException
     * @throws UnauthorizedException
     * @throws UnexpectedErrorException
     * @throws ValidationException
     * @throws ClientExceptionInterface
     */
    public function request(
        string $method,
        string $uri,
        array $content = [],
        array $queryParams = []
    ): array {
        // Build full URL
        $url = $this->buildUrl($uri, $queryParams);

        // Build request body
        $body = !empty($content) ? json_encode($content, JSON_THROW_ON_ERROR) : '';

        // Create and send request
        $request = new Request(
            $method,
            $url,
            $this->buildHeaders(),
            $body
        );

        $response = $this->client->sendRequest($request);

        return $this->handleResponse($response);
    }

    /**
     * Build the complete API URL
     */
    protected function buildUrl(string $uri, array $queryParams = []): string
    {
        // Remove leading slash if present
        $uri = ltrim($uri, '/');

        // Build base URL
        $url = self::API_BASE_URL . '/' . self::API_VERSION . '/' . $uri;

        // Add .json extension if not present
        if (!str_ends_with($url, '.json')) {
            $url .= '.json';
        }

        // Add query parameters
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }

    /**
     * Build request headers
     *
     * @return array<string, string>
     */
    protected function buildHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/vnd.api+json',
            'Authorization-Type' => $this->auth->getAuthorizationType(),
            'Authorization' => $this->auth->getAuthorizationHeader(),
            'User-Agent' => "ChallongePHP/{$this->version} (https://github.com/teamreflex/ChallongePHP)",
        ];
    }

    /**
     * Handle the API response
     *
     * @return array<string, mixed>
     *
     * @throws JsonException
     * @throws UnauthorizedException
     * @throws NotFoundException
     * @throws InvalidFormatException
     * @throws ValidationException
     * @throws ServerException
     * @throws UnexpectedErrorException
     */
    protected function handleResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        // Handle successful responses
        if ($statusCode >= 200 && $statusCode < 300) {
            // Handle 204 No Content
            if ($statusCode === 204 || empty($body)) {
                return [];
            }

            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        // Parse error response
        $errorData = null;
        try {
            $errorData = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            // If JSON parsing fails, use the raw body
        }

        // Handle errors based on status code
        match ($statusCode) {
            400 => throw new ValidationException(
                $this->formatErrorMessage($errorData, 'Bad Request - Invalid parameters or payload format')
            ),
            401 => throw new UnauthorizedException(
                $this->formatErrorMessage($errorData, 'Unauthorized - Invalid authentication credentials')
            ),
            403 => throw new UnauthorizedException(
                $this->formatErrorMessage($errorData, 'Forbidden - Missing required permissions')
            ),
            404 => throw new NotFoundException(
                $this->formatErrorMessage($errorData, 'Not Found - Resource does not exist')
            ),
            406 => throw new InvalidFormatException(
                $this->formatErrorMessage($errorData, 'Not Acceptable - Invalid content type')
            ),
            415 => throw new InvalidFormatException(
                $this->formatErrorMessage($errorData, 'Unsupported Media Type')
            ),
            422 => throw new ValidationException(
                $this->formatErrorMessage($errorData, 'Unprocessable Entity - Validation errors'),
                $errorData
            ),
            500, 502, 503, 504 => throw new ServerException(
                $this->formatErrorMessage($errorData, "Server error ({$statusCode})")
            ),
            default => throw new UnexpectedErrorException(
                $this->formatErrorMessage($errorData, "Unexpected error ({$statusCode})"),
                $errorData
            ),
        };
    }

    /**
     * Format error message from v2.1 error response
     *
     * v2.1 error format:
     * {
     *   "errors": [
     *     {
     *       "status": 422,
     *       "detail": "Tournament Format is invalid",
     *       "source": {"pointer": "/data/attributes/tournament_format"}
     *     }
     *   ]
     * }
     */
    protected function formatErrorMessage(?array $errorData, string $fallback): string
    {
        if ($errorData === null) {
            return $fallback;
        }

        // Check for v2.1 error format
        if (isset($errorData['errors']) && is_array($errorData['errors'])) {
            $messages = [];
            foreach ($errorData['errors'] as $error) {
                if (isset($error['detail'])) {
                    $message = $error['detail'];
                    if (isset($error['source']['pointer'])) {
                        $message .= " (field: {$error['source']['pointer']})";
                    }
                    $messages[] = $message;
                }
            }

            if (!empty($messages)) {
                return implode('; ', $messages);
            }
        }

        // Fallback to any error message in the response
        if (isset($errorData['message'])) {
            return $errorData['message'];
        }

        return $fallback;
    }

    /**
     * Challonge v1 required input in format ["tournament[name]" => "test"]
     * v2.1 uses JSON API format, but this method is kept for backwards compatibility
     *
     * @param array<string, mixed> $options
     * @param string $scope Resource type (e.g., 'tournament', 'participant')
     * @return array<string, mixed>
     */
    public function mapOptions(array $options, string $scope): array
    {
        if (!$this->mapOptions) {
            return $options;
        }

        $keys = array_map(fn (string $key) => "{$scope}[{$key}]", array_keys($options));
        return array_combine($keys, array_values($options)) ?: [];
    }

    /**
     * Get the authentication provider
     */
    public function getAuth(): AuthenticationInterface
    {
        return $this->auth;
    }

    /**
     * Set the authentication provider
     */
    public function setAuth(AuthenticationInterface $auth): void
    {
        $this->auth = $auth;
    }

    /**
     * Get the underlying HTTP client
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * Get the option mapping setting
     */
    public function getMapOptions(): bool
    {
        return $this->mapOptions;
    }

    /**
     * Set the option mapping setting
     */
    public function setMapOptions(bool $mapOptions): void
    {
        $this->mapOptions = $mapOptions;
    }

    /**
     * @deprecated Use getAuth() instead - kept for backwards compatibility
     */
    public function getKey(): string
    {
        if (method_exists($this->auth, 'getApiKey')) {
            return $this->auth->getApiKey();
        }

        return '';
    }

    /**
     * @deprecated Authentication should be set via constructor - kept for backwards compatibility
     */
    public function setKey(string $key): void
    {
        if (method_exists($this->auth, 'getApiKey')) {
            // Can't change immutable API key auth, would need to create new instance
            throw new \RuntimeException('Cannot change API key on immutable authentication provider. Create a new ClientWrapper instance instead.');
        }
    }
}
