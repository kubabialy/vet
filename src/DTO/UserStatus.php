<?php

namespace Vet\Vet\DTO;

readonly class UserStatus
{
    public const string ACTIVE = 'active';
    public const string INACTIVE = 'inactive';
    public const string BANNED = 'banned';
    public const string DELETED = 'deleted';
    public const string DISABLED = 'disabled';
}