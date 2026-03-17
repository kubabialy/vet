<?php

namespace Vet\Vet\Auth;

use Firebase\JWT\JWT;
use RuntimeException;
use Vet\Vet\Database\User;
use Vet\Vet\DTO\UserStatus;

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

    const int TOKEN_TTL = 3600;
    /**
     * Authenticates a user and generates a JWT token if successful.
     *
     * @param User $user The user to authenticate.
     * @param string $password The password provided for authentication.
     * @return AuthResult The result of the authentication process.
     * @throws RuntimeException If an unexpected error occurs.
     */
    public static function authenticate(
        User   $user,
        string $password,
        string $key,
        string $iss,
        string $aud
    ): AuthResult
    {
        if ($user->status === UserStatus::DELETED) return AuthResult::error(AuthResult::ERROR_USER_DELETED);
        if ($user->status === UserStatus::BANNED) return AuthResult::error(AuthResult::ERROR_USER_BANNED);
        if ($user->status === UserStatus::DISABLED) return AuthResult::error(AuthResult::ERROR_USER_DISABLED);
        if ($user->status === UserStatus::INACTIVE) return AuthResult::error(AuthResult::ERROR_USER_INACTIVE);

        if (empty($password)) return AuthResult::error(AuthResult::ERROR_PASSWORD_EMPTY);

        if (!password_verify($password, $user->hashedPassword))
            return AuthResult::error(AuthResult::ERROR_INVALID_CREDENTIALS);

        $time = time();
        $payload = [
            'iss' => $iss,
            'aud' => $aud,
            'sub' => $user->id,
            'iat' => $time,
            'exp' => $time + self::TOKEN_TTL,
            'nbf' => $time,
        ];

        return AuthResult::success(JWT::encode($payload, $key, self::DEFAULT_ALGORITHM));
    }
}
