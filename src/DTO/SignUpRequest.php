<?php

declare(strict_types=1);

namespace Vet\Vet\DTO;

/**
 * SignUpRequest represents the data required for user registration.
 *
 * This is a data transfer object containing the user's email,
 * password, and password confirmation for the signup process.
 */
readonly class SignUpRequest
{
    public function __construct(
        public string $email,
        public string $password,
        public string $repeatedPassword,
    ) {}
}
