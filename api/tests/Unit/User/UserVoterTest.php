<?php

declare(strict_types=1);

namespace App\Tests\Unit\User;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use App\User\Infrastructure\Security\Voter\UserVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class UserVoterTest extends TestCase
{
    private UserVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new UserVoter();
    }

    // --- LIST ---

    public function testManagerCanListUsers(): void
    {
        $this->assertVote(VoterInterface::ACCESS_GRANTED, UserVoter::LIST, $this->makeUser(UserRole::MANAGER));
    }

    public function testReporterCannotListUsers(): void
    {
        $this->assertVote(VoterInterface::ACCESS_DENIED, UserVoter::LIST, $this->makeUser(UserRole::REPORTER));
    }

    public function testAgentCannotListUsers(): void
    {
        $this->assertVote(VoterInterface::ACCESS_DENIED, UserVoter::LIST, $this->makeUser(UserRole::AGENT));
    }

    // --- CREATE ---

    public function testManagerCanCreateUser(): void
    {
        $this->assertVote(VoterInterface::ACCESS_GRANTED, UserVoter::CREATE, $this->makeUser(UserRole::MANAGER));
    }

    public function testReporterCannotCreateUser(): void
    {
        $this->assertVote(VoterInterface::ACCESS_DENIED, UserVoter::CREATE, $this->makeUser(UserRole::REPORTER));
    }

    public function testAgentCannotCreateUser(): void
    {
        $this->assertVote(VoterInterface::ACCESS_DENIED, UserVoter::CREATE, $this->makeUser(UserRole::AGENT));
    }

    // --- VIEW ---

    public function testManagerCanViewUser(): void
    {
        $target = $this->makeUser(UserRole::REPORTER);
        $this->assertVote(VoterInterface::ACCESS_GRANTED, UserVoter::VIEW, $this->makeUser(UserRole::MANAGER), $target);
    }

    public function testReporterCannotViewUser(): void
    {
        $target = $this->makeUser(UserRole::AGENT);
        $this->assertVote(VoterInterface::ACCESS_DENIED, UserVoter::VIEW, $this->makeUser(UserRole::REPORTER), $target);
    }

    public function testAgentCannotViewUser(): void
    {
        $target = $this->makeUser(UserRole::REPORTER);
        $this->assertVote(VoterInterface::ACCESS_DENIED, UserVoter::VIEW, $this->makeUser(UserRole::AGENT), $target);
    }

    // --- UPDATE ---

    public function testManagerCanUpdateUser(): void
    {
        $target = $this->makeUser(UserRole::REPORTER);
        $this->assertVote(VoterInterface::ACCESS_GRANTED, UserVoter::UPDATE, $this->makeUser(UserRole::MANAGER), $target);
    }

    public function testReporterCannotUpdateUser(): void
    {
        $target = $this->makeUser(UserRole::AGENT);
        $this->assertVote(VoterInterface::ACCESS_DENIED, UserVoter::UPDATE, $this->makeUser(UserRole::REPORTER), $target);
    }

    // --- helpers ---

    private function assertVote(int $expected, string $attribute, User $actor, ?User $subject = null): void
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($actor);

        $result = $this->voter->vote($token, $subject, [$attribute]);
        $this->assertSame($expected, $result);
    }

    private function makeUser(UserRole $role): User
    {
        return new User('user@test.com', '', 'Test', $role);
    }
}
