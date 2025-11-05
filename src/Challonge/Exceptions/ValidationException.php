<?php

declare(strict_types=1);

namespace Reflex\Challonge\Exceptions;

use Exception;

/**
 * Thrown when the API returns a validation error (400 or 422 status code)
 */
class ValidationException extends Exception
{
    /**
     * @param array<string, mixed>|null $errors
     */
    public function __construct(
        string $message = '',
        private readonly ?array $errors = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the validation errors from the API response
     *
     * @return array<string, mixed>|null
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }
}
