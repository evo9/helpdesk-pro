<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Enum\TicketStatus;
use App\Ticket\Infrastructure\Security\Voter\TicketVoter;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class TicketVoterTest extends TestCase
{
    private TicketVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new TicketVoter();
    }

    // --- VIEW ---

    public function testReporterCanViewOwnTicket(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, TicketVoter::VIEW, $reporter, $ticket);
    }

    public function testReporterCannotViewOtherTicket(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $other = $this->makeUser(UserRole::REPORTER, 'other@test.com');
        $ticket = $this->makeTicket($other);

        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::VIEW, $reporter, $ticket);
    }

    public function testAgentCanViewAssignedTicket(): void
    {
        $agent = $this->makeUser(UserRole::AGENT);
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter, $agent);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, TicketVoter::VIEW, $agent, $ticket);
    }

    public function testAgentCanViewUnassignedTicket(): void
    {
        $agent = $this->makeUser(UserRole::AGENT);
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, TicketVoter::VIEW, $agent, $ticket);
    }

    public function testAgentCannotViewTicketAssignedToOtherAgent(): void
    {
        $agent = $this->makeUser(UserRole::AGENT);
        $otherAgent = $this->makeUser(UserRole::AGENT, 'other@test.com');
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter, $otherAgent);

        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::VIEW, $agent, $ticket);
    }

    public function testManagerCanViewAnyTicket(): void
    {
        $manager = $this->makeUser(UserRole::MANAGER);
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, TicketVoter::VIEW, $manager, $ticket);
    }

    // --- CREATE ---

    public function testReporterCanCreate(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $this->assertVote(VoterInterface::ACCESS_GRANTED, TicketVoter::CREATE, $reporter, null);
    }

    public function testAgentCannotCreate(): void
    {
        $agent = $this->makeUser(UserRole::AGENT);
        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::CREATE, $agent, null);
    }

    public function testManagerCannotCreate(): void
    {
        $manager = $this->makeUser(UserRole::MANAGER);
        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::CREATE, $manager, null);
    }

    // --- UPDATE_STATUS ---

    public function testAgentCanUpdateStatusOfOwnTicket(): void
    {
        $agent = $this->makeUser(UserRole::AGENT);
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter, $agent);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, TicketVoter::UPDATE_STATUS, $agent, $ticket);
    }

    public function testAgentCannotUpdateStatusOfUnassignedTicket(): void
    {
        $agent = $this->makeUser(UserRole::AGENT);
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::UPDATE_STATUS, $agent, $ticket);
    }

    public function testAgentCannotUpdateStatusOfOtherAgentTicket(): void
    {
        $agent = $this->makeUser(UserRole::AGENT);
        $otherAgent = $this->makeUser(UserRole::AGENT, 'other@test.com');
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter, $otherAgent);

        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::UPDATE_STATUS, $agent, $ticket);
    }

    public function testManagerCanUpdateStatusOfAnyTicket(): void
    {
        $manager = $this->makeUser(UserRole::MANAGER);
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, TicketVoter::UPDATE_STATUS, $manager, $ticket);
    }

    public function testReporterCannotUpdateStatus(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::UPDATE_STATUS, $reporter, $ticket);
    }

    // --- ASSIGN ---

    public function testManagerCanAssign(): void
    {
        $manager = $this->makeUser(UserRole::MANAGER);
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, TicketVoter::ASSIGN, $manager, $ticket);
    }

    public function testAgentCannotAssign(): void
    {
        $agent = $this->makeUser(UserRole::AGENT);
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter, $agent);

        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::ASSIGN, $agent, $ticket);
    }

    public function testReporterCannotAssign(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::ASSIGN, $reporter, $ticket);
    }

    // --- DELETE ---

    public function testManagerCanDelete(): void
    {
        $manager = $this->makeUser(UserRole::MANAGER);
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, TicketVoter::DELETE, $manager, $ticket);
    }

    public function testAgentCannotDelete(): void
    {
        $agent = $this->makeUser(UserRole::AGENT);
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::DELETE, $agent, $ticket);
    }

    public function testReporterCannotDelete(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::DELETE, $reporter, $ticket);
    }

    // --- REOPEN ---

    public function testReporterCanReopenOwnResolvedTicketWithin72h(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeResolvedTicket($reporter, 1);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, TicketVoter::REOPEN, $reporter, $ticket);
    }

    public function testReporterCannotReopenExpiredResolvedTicket(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeResolvedTicket($reporter, 73);

        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::REOPEN, $reporter, $ticket);
    }

    public function testReporterCannotReopenOtherReporterTicket(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $other = $this->makeUser(UserRole::REPORTER, 'other@test.com');
        $ticket = $this->makeResolvedTicket($other, 1);

        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::REOPEN, $reporter, $ticket);
    }

    public function testReporterCannotReopenNonResolvedTicket(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_DENIED, TicketVoter::REOPEN, $reporter, $ticket);
    }

    // --- helpers ---

    private function assertVote(int $expected, string $attribute, User $user, ?Ticket $subject): void
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, $subject, [$attribute]);
        $this->assertSame($expected, $result);
    }

    private function makeUser(UserRole $role, string $email = 'user@test.com'): User
    {
        return new User($email, '', 'Test User', $role);
    }

    private function makeTicket(User $reporter, ?User $assignee = null): Ticket
    {
        $ticket = new Ticket('Title', 'Desc', TicketPriority::MEDIUM, new Category('Test'), $reporter, null, null, null);
        if (null !== $assignee) {
            $ticket->assignTo($assignee);
        }

        return $ticket;
    }

    private function makeResolvedTicket(User $reporter, int $resolvedHoursAgo): Ticket
    {
        $ticket = $this->makeTicket($reporter);
        $ticket->changeStatus(TicketStatus::RESOLVED);
        $ticket->markResolved();

        $ref = new \ReflectionProperty(Ticket::class, 'resolvedAt');
        $ref->setValue($ticket, new \DateTimeImmutable("-{$resolvedHoursAgo} hours"));

        return $ticket;
    }
}
