<?php

declare(strict_types=1);

namespace Vet\Vet\Database;

readonly class User
{
    public function __construct(
        public int $id,
        public string $email,
        public string $hashedPassword,
        public bool $isLogable,
        public bool $isDeleted,
        public bool $isActive,
        public ?string $banReason,
        public ?string $disableReason,
    ) {}
}