<?php

declare(strict_types=1);

namespace Vet\Vet\DTO;

/**
 * ErrorResponse represents a standardized error response.
 *
 * This is a data transfer object used when returning error
 * responses to the client in a consistent format.
 */
readonly class ErrorResponse
{
    public function __construct(
        public bool $success = false,
        public string $message = '',
        public ?string $errorCode = null,
    ) {}
}
