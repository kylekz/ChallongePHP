<?php

declare(strict_types=1);

namespace Reflex\Challonge\Mapper;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Tree\Message\Messages;
use CuyZ\Valinor\MapperBuilder;

/**
 * Valinor-based data mapper with flexible mapping for the Challonge API
 *
 * Handles missing fields gracefully since Challonge's API is not stable
 * and frequently adds new fields.
 */
class Mapper
{
    private \CuyZ\Valinor\Mapper\TreeMapper $mapper;

    public function __construct()
    {
        $this->mapper = (new MapperBuilder())
            ->allowSuperfluousKeys() // Allow API to return extra fields we don't define
            ->allowPermissiveTypes() // Allow flexible type coercion
            ->enableFlexibleCasting() // Enable automatic type casting
            ->filterExceptions(function (\Throwable $exception) {
                // Log mapping errors but don't fail for missing optional fields
                if ($exception instanceof MappingError) {
                    $messages = Messages::flattenFromNode($exception->node());

                    // Filter out "missing value" errors for optional fields
                    foreach ($messages as $message) {
                        if (!str_contains($message->toString(), 'Cannot be empty')) {
                            // Re-throw non-optional field errors
                            throw $exception;
                        }
                    }

                    return;
                }

                throw $exception;
            })
            ->mapper();
    }

    /**
     * Map data to a specific class
     *
     * @template T of object
     * @param class-string<T> $className
     * @param array<string, mixed> $data
     * @return T
     * @throws MappingError
     */
    public function map(string $className, array $data): object
    {
        return $this->mapper->map($className, $data);
    }

    /**
     * Map an array of data to an array of objects
     *
     * @template T of object
     * @param class-string<T> $className
     * @param array<array<string, mixed>> $dataArray
     * @return array<T>
     */
    public function mapArray(string $className, array $dataArray): array
    {
        $results = [];

        foreach ($dataArray as $data) {
            $results[] = $this->map($className, $data);
        }

        return $results;
    }

    /**
     * Get the underlying Valinor mapper
     */
    public function getMapper(): \CuyZ\Valinor\Mapper\TreeMapper
    {
        return $this->mapper;
    }
}
