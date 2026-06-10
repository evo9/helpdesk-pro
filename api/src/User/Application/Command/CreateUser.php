<?php

declare(strict_types=1);

namespace App\User\Application\Command;

final class CreateUser
{
    public function __construct(
        public readonly string $email,
        public readonly string $plainPassword,
        public readonly string $fullName,
        public readonly string $role,
    ) {
    }
}
