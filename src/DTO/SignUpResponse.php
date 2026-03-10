<?php

declare(strict_types=1);

namespace Vet\Vet\DTO;

/**
 * SignUpResponse represents the response after a successful user registration.
 *
 * This is a data transfer object containing the response data
 * returned to the client after a signup attempt.
 */
readonly class SignUpResponse
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?int $userId = null,
    ) {}

    /**
     * Creates a successful signup response.
     *
     * @param int $userId The ID of the newly created user.
     * @return self A successful response instance.
     */
    public static function success(int $userId): self
    {
        return new self(
            success: true,
            message: 'User registered successfully',
            userId: $userId,
        );
    }

    /**
     * Creates an error signup response.
     *
     * @param string $message The error message.
     * @return self An error response instance.
     */
    public static function error(string $message): self
    {
        return new self(
            success: false,
            message: $message,
            userId: null,
        );
    }
}
