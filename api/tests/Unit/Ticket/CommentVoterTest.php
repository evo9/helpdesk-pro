<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Comment;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Infrastructure\Security\Voter\CommentVoter;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class CommentVoterTest extends TestCase
{
    private CommentVoter $voter;

    protected function setUp(): void
    {
        $checker = $this->createStub(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturnCallback(
            static function (string $attr, mixed $subject): bool {
                // Delegate to TicketVoter logic inline for test purposes:
                // Reporters can view their own ticket; agents can view unassigned + assigned to them.
                return true; // simplified: allow ticket view for all in these tests
            }
        );
        $this->voter = new CommentVoter($checker);
    }

    // --- VIEW ---

    public function testReporterCanViewPublicCommentOnOwnTicket(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);
        $comment = new Comment($ticket, $reporter, 'Hello', false);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, CommentVoter::VIEW, $reporter, $comment);
    }

    public function testReporterCannotViewInternalCommentOnOwnTicket(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);
        $agent = $this->makeUser(UserRole::AGENT);
        $comment = new Comment($ticket, $agent, 'Internal note', true);

        $this->assertVote(VoterInterface::ACCESS_DENIED, CommentVoter::VIEW, $reporter, $comment);
    }

    public function testAgentCanViewInternalComment(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);
        $agent = $this->makeUser(UserRole::AGENT);
        $comment = new Comment($ticket, $agent, 'Internal note', true);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, CommentVoter::VIEW, $agent, $comment);
    }

    public function testManagerCanViewInternalComment(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);
        $agent = $this->makeUser(UserRole::AGENT);
        $manager = $this->makeUser(UserRole::MANAGER);
        $comment = new Comment($ticket, $agent, 'Internal note', true);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, CommentVoter::VIEW, $manager, $comment);
    }

    // --- CREATE ---

    public function testReporterOfTicketCanCreateComment(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, CommentVoter::CREATE, $reporter, $ticket);
    }

    public function testOtherReporterCannotCreateCommentOnSomeoneElsesTicket(): void
    {
        $owner = $this->makeUser(UserRole::REPORTER);
        $other = $this->makeUser(UserRole::REPORTER, 'other@test.com');
        $ticket = $this->makeTicket($owner);

        $this->assertVote(VoterInterface::ACCESS_DENIED, CommentVoter::CREATE, $other, $ticket);
    }

    public function testAssignedAgentCanCreateComment(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $agent = $this->makeUser(UserRole::AGENT);
        $ticket = $this->makeTicket($reporter, $agent);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, CommentVoter::CREATE, $agent, $ticket);
    }

    public function testUnassignedAgentCannotCreateComment(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $agent = $this->makeUser(UserRole::AGENT);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_DENIED, CommentVoter::CREATE, $agent, $ticket);
    }

    public function testManagerCanAlwaysCreateComment(): void
    {
        $reporter = $this->makeUser(UserRole::REPORTER);
        $manager = $this->makeUser(UserRole::MANAGER);
        $ticket = $this->makeTicket($reporter);

        $this->assertVote(VoterInterface::ACCESS_GRANTED, CommentVoter::CREATE, $manager, $ticket);
    }

    // --- helpers ---

    private function assertVote(int $expected, string $attribute, User $user, Comment|Ticket $subject): void
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
}
