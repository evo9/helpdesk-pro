<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testCreatesUserWithRequiredFields(): void
    {
        $user = new User(
            email: 'alice@example.com',
            passwordHash: '$2y$13$hash',
            fullName: 'Alice Smith',
            role: UserRole::REPORTER,
        );

        $this->assertSame('alice@example.com', $user->getEmail());
        $this->assertSame('$2y$13$hash', $user->getPassword());
        $this->assertSame('Alice Smith', $user->getFullName());
        $this->assertSame(UserRole::REPORTER, $user->getRole());
        $this->assertTrue($user->isActive());
    }

    public function testGeneratesUuidOnCreation(): void
    {
        $user1 = new User('a@a.com', 'hash', 'A', UserRole::AGENT);
        $user2 = new User('b@b.com', 'hash', 'B', UserRole::AGENT);

        $this->assertNotNull($user1->getId());
        $this->assertNotEquals($user1->getId(), $user2->getId());
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $user = new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT);

        $this->assertSame('agent@example.com', $user->getUserIdentifier());
    }

    public function testGetRolesReturnsSecurityRole(): void
    {
        $manager = new User('mgr@example.com', 'hash', 'Manager', UserRole::MANAGER);

        $this->assertContains('ROLE_MANAGER', $manager->getRoles());
    }

    public function testDeactivate(): void
    {
        $user = new User('u@example.com', 'hash', 'U', UserRole::REPORTER);
        $user->deactivate();

        $this->assertFalse($user->isActive());
    }
}
