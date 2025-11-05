<?php

declare(strict_types=1);

namespace Reflex\Challonge\Exceptions;

use Exception;

/**
 * Thrown when an unexpected error occurs
 */
class UnexpectedErrorException extends Exception
{
    /**
     * @param array<string, mixed>|null $response
     */
    public function __construct(
        string $message = 'An unexpected error occurred',
        private readonly ?array $response = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the raw response data
     *
     * @return array<string, mixed>|null
     */
    public function getResponse(): ?array
    {
        return $this->response;
    }
}
