<?php

namespace Vet\Vet\Auth;

use Firebase\JWT\JWT;
use RuntimeException;
use Vet\Vet\Database\User;

/**
 * Class Auth
 * Handles user authentication and JWT token generation.
 */
class Auth
{
    /**
     * Default algorithm used for JWT encoding.
     */
    const string DEFAULT_ALGORITHM = 'HS256';

    /**
     * Auth constructor.
     *
     * @param string $key Secret key for JWT encoding.
     * @param string $iss Issuer claim for JWT payload.
     * @param string $aud Audience claim for JWT payload.
     */
    public function __construct(
        private readonly string $key,
        private readonly string $iss = "http://localhost:8080",
        private readonly string $aud = "http://localhost:8000",
    )
    {
    }

    /**
     * Authenticates a user and generates a JWT token if successful.
     *
     * @param User $user The user to authenticate.
     * @param string $password The password provided for authentication.
     * @return AuthResult The result of the authentication process.
     * @throws RuntimeException If an unexpected error occurs.
     */
    public function authenticate(User $user, string $password): AuthResult
    {
        // Disabled or banned user.
        if (!$user->isLogable && !$user->isDeleted && $user->isActive) {
            if ($user->banReason) return AuthResult::error(AuthResult::ERROR_USER_BANNED);

            if ($user->disableReason) return AuthResult::error(AuthResult::ERROR_USER_DISABLED);

            throw new RuntimeException('Something went terribly wrong. Please contact support');
        }

        if (!$user->isActive) return AuthResult::error(AuthResult::ERROR_USER_INACTIVE);

        if ($user->isDeleted) return AuthResult::error(AuthResult::ERROR_USER_DELETED);

        if (empty($password)) return AuthResult::error(AuthResult::ERROR_PASSWORD_EMPTY);

        if (!password_verify($password, $user->hashedPassword))
            return AuthResult::error(AuthResult::ERROR_INVALID_CREDENTIALS);

        $payload = [
            'iss' => $this->iss,
            'aud' => $this->aud,
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + 3600,
            'nbf' => time(),
        ];

        return AuthResult::success(JWT::encode($payload, $this->key, self::DEFAULT_ALGORITHM));
    }
}