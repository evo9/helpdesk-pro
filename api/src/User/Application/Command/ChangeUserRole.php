<?php

declare(strict_types=1);

namespace App\User\Application\Command;

final class ChangeUserRole
{
    public function __construct(
        public readonly string $userId,
        public readonly string $role,
    ) {
    }
}
