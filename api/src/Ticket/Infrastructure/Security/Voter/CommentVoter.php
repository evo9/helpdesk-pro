<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Security\Voter;

use App\Ticket\Domain\Entity\Comment;
use App\Ticket\Domain\Entity\Ticket;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Comment|Ticket>
 */
final class CommentVoter extends Voter
{
    public const VIEW = 'COMMENT_VIEW';
    public const CREATE = 'COMMENT_CREATE';

    public function __construct(
        private readonly AuthorizationCheckerInterface $authChecker,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return match ($attribute) {
            self::VIEW => $subject instanceof Comment,
            self::CREATE => $subject instanceof Ticket,
            default => false,
        };
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user),    // @phpstan-ignore-line
            self::CREATE => $this->canCreate($subject, $user), // @phpstan-ignore-line
            default => false,
        };
    }

    private function canView(Comment $comment, User $user): bool
    {
        if (!$this->authChecker->isGranted(TicketVoter::VIEW, $comment->getTicket())) {
            return false;
        }

        return !($comment->isInternal() && UserRole::REPORTER === $user->getRole());
    }

    private function canCreate(Ticket $ticket, User $user): bool
    {
        return match ($user->getRole()) {
            UserRole::REPORTER => $ticket->getReporter() === $user,
            UserRole::AGENT => $ticket->getAssignee() === $user,
            UserRole::MANAGER => true,
        };
    }
}
