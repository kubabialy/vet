<?php

declare(strict_types=1);

namespace Vet\Vet\DTO;

/**
 * SignInRequest represents the data required for user authentication.
 *
 * This is a data transfer object containing the user's credentials
 * (email and password) for the sign-in process.
 */
readonly class SignInRequest
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
