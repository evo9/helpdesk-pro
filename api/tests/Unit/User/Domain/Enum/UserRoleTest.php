<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Enum;

use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;

final class UserRoleTest extends TestCase
{
    public function testCasesHaveCorrectValues(): void
    {
        $this->assertSame('reporter', UserRole::REPORTER->value);
        $this->assertSame('agent', UserRole::AGENT->value);
        $this->assertSame('manager', UserRole::MANAGER->value);
    }

    public function testToSecurityRoleReturnsSymfonyRole(): void
    {
        $this->assertSame('ROLE_REPORTER', UserRole::REPORTER->toSecurityRole());
        $this->assertSame('ROLE_AGENT', UserRole::AGENT->toSecurityRole());
        $this->assertSame('ROLE_MANAGER', UserRole::MANAGER->toSecurityRole());
    }
}
