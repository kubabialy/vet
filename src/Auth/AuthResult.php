<?php

namespace Vet\Vet\Auth;

final readonly class AuthResult
{
    public const string ERROR_INVALID_CREDENTIALS = 'invalid_credentials';
    public const string ERROR_PASSWORD_EMPTY = 'password_empty';
    public const string ERROR_USER_NOT_FOUND = 'user_not_found';
    public const string ERROR_USER_BANNED = 'user_banned';
    public const string ERROR_USER_INACTIVE = 'user_inactive';
    public const string ERROR_USER_DISABLED = 'user_disabled';
    public const string ERROR_USER_DELETED = 'user_deleted';
    public function __construct(
        public ?string $token,
        public bool    $hasError,
        public ?string $error,
    )
    {
    }

    public static function success(string $token): self
    {
        return new self($token, false, null);
    }

    public static function error(string $error): self
    {
        return new self(null, true, $error);
    }
}