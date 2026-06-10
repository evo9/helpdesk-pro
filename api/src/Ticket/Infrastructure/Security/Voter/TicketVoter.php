<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Security\Voter;

use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Enum\TicketStatus;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Ticket|null>
 */
final class TicketVoter extends Voter
{
    public const VIEW = 'TICKET_VIEW';
    public const CREATE = 'TICKET_CREATE';
    public const UPDATE_STATUS = 'TICKET_UPDATE_STATUS';
    public const ASSIGN = 'TICKET_ASSIGN';
    public const DELETE = 'TICKET_DELETE';
    public const REOPEN = 'TICKET_REOPEN';

    private const ATTRIBUTES = [
        self::VIEW,
        self::CREATE,
        self::UPDATE_STATUS,
        self::ASSIGN,
        self::DELETE,
        self::REOPEN,
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, self::ATTRIBUTES, true)) {
            return false;
        }

        if (self::CREATE === $attribute) {
            return true;
        }

        return $subject instanceof Ticket;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (self::CREATE === $attribute) {
            return UserRole::REPORTER === $user->getRole();
        }

        /** @var Ticket $ticket */
        $ticket = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($ticket, $user),
            self::UPDATE_STATUS => $this->canUpdateStatus($ticket, $user),
            self::ASSIGN => UserRole::MANAGER === $user->getRole(),
            self::DELETE => UserRole::MANAGER === $user->getRole(),
            self::REOPEN => $this->canReopen($ticket, $user),
            default => false,
        };
    }

    private function canView(Ticket $ticket, User $user): bool
    {
        return match ($user->getRole()) {
            UserRole::REPORTER => $ticket->getReporter() === $user,
            UserRole::AGENT => $ticket->getAssignee() === $user || null === $ticket->getAssignee(),
            UserRole::MANAGER => true,
        };
    }

    private function canUpdateStatus(Ticket $ticket, User $user): bool
    {
        return match ($user->getRole()) {
            UserRole::REPORTER => false,
            UserRole::AGENT => $ticket->getAssignee() === $user,
            UserRole::MANAGER => true,
        };
    }

    private function canReopen(Ticket $ticket, User $user): bool
    {
        if ($ticket->getReporter() !== $user) {
            return false;
        }

        if (TicketStatus::RESOLVED !== $ticket->getStatus()) {
            return false;
        }

        $resolvedAt = $ticket->getResolvedAt();
        if (null === $resolvedAt) {
            return false;
        }

        return $resolvedAt > new \DateTimeImmutable('-72 hours');
    }
}
