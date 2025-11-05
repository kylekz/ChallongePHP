<?php

declare(strict_types=1);

namespace Reflex\Challonge;

use Reflex\Challonge\Mapper\Mapper;

/**
 * Trait for DTOs that need to interact with the Challonge API client
 */
trait DtoClientTrait
{
    protected ClientWrapper $client;
    protected static ?Mapper $mapper = null;

    /**
     * Create a DTO instance from API response data
     *
     * @param ClientWrapper $client
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromResponse(ClientWrapper $client, array $data): self
    {
        $mapper = self::getMapper();
        /** @var static $dto */
        $dto = $mapper->map(static::class, $data);
        $dto->setClient($client);

        return $dto;
    }

    /**
     * Set the API client
     */
    public function setClient(ClientWrapper $client): void
    {
        $this->client = $client;
    }

    /**
     * Get or create the Valinor mapper instance
     */
    protected static function getMapper(): Mapper
    {
        if (self::$mapper === null) {
            self::$mapper = new Mapper();
        }

        return self::$mapper;
    }
}
