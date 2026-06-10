<?php

declare(strict_types=1);

namespace App\User\Domain\Enum;

enum UserRole: string
{
    case REPORTER = 'reporter';
    case AGENT = 'agent';
    case MANAGER = 'manager';

    public function toSecurityRole(): string
    {
        return match ($this) {
            self::REPORTER => 'ROLE_REPORTER',
            self::AGENT => 'ROLE_AGENT',
            self::MANAGER => 'ROLE_MANAGER',
        };
    }
}
