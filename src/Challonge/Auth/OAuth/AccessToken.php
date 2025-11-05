<?php

declare(strict_types=1);

namespace Reflex\Challonge\Auth\OAuth;

use DateTimeImmutable;

/**
 * Represents an OAuth access token with refresh capability
 */
final class AccessToken
{
    public function __construct(
        private readonly string $accessToken,
        private readonly string $tokenType,
        private readonly int $expiresIn,
        private readonly ?string $refreshToken = null,
        private readonly ?string $scope = null,
        private readonly ?DateTimeImmutable $createdAt = null,
    ) {
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt ?? new DateTimeImmutable();
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->getCreatedAt()->modify("+{$this->expiresIn} seconds");
    }

    public function isExpired(): bool
    {
        return $this->getExpiresAt() <= new DateTimeImmutable();
    }

    public function hasRefreshToken(): bool
    {
        return $this->refreshToken !== null;
    }

    /**
     * Create from API response array
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $createdAt = isset($data['created_at'])
            ? (new DateTimeImmutable())->setTimestamp((int) $data['created_at'])
            : new DateTimeImmutable();

        return new self(
            accessToken: (string) $data['access_token'],
            tokenType: (string) ($data['token_type'] ?? 'Bearer'),
            expiresIn: (int) ($data['expires_in'] ?? 604800), // Default to 1 week
            refreshToken: isset($data['refresh_token']) ? (string) $data['refresh_token'] : null,
            scope: isset($data['scope']) ? (string) $data['scope'] : null,
            createdAt: $createdAt,
        );
    }

    /**
     * Convert to array for storage
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'refresh_token' => $this->refreshToken,
            'scope' => $this->scope,
            'created_at' => $this->getCreatedAt()->getTimestamp(),
        ];
    }
}
