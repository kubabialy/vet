<?php

declare(strict_types=1);

namespace Vet\Vet\DTO;

/**
 * SignInResponse represents the response after a successful user authentication.
 *
 * This is a data transfer object containing the JWT token
 * and user information returned after a successful sign-in.
 */
readonly class SignInResponse
{
    public function __construct(
        public bool $success,
        public ?string $token = null,
        public ?int $userId = null,
        public ?string $message = null,
    ) {}

    /**
     * Creates a successful sign-in response.
     *
     * @param string $token The JWT authentication token.
     * @param int $userId The ID of the authenticated user.
     * @return self A successful response instance.
     */
    public static function success(string $token, int $userId): self
    {
        return new self(
            success: true,
            token: $token,
            userId: $userId,
            message: 'Signed in successfully',
        );
    }

    /**
     * Creates an error sign-in response.
     *
     * @param string $message The error message.
     * @return self An error response instance.
     */
    public static function error(string $message): self
    {
        return new self(
            success: false,
            message: $message,
        );
    }
}
