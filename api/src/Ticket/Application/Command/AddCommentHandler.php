<?php

declare(strict_types=1);

namespace App\Ticket\Application\Command;

use App\Ticket\Domain\Entity\Comment;
use App\Ticket\Domain\Exception\TicketNotFoundException;
use App\Ticket\Domain\Repository\CommentRepositoryInterface;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\User\Domain\Enum\UserRole;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class AddCommentHandler
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly UserRepositoryInterface $userRepo,
        private readonly CommentRepositoryInterface $commentRepo,
    ) {
    }

    public function __invoke(AddComment $command): Comment
    {
        $ticket = $this->ticketRepo->findById(Uuid::fromString($command->ticketId))
            ?? throw new TicketNotFoundException($command->ticketId);

        $author = $this->userRepo->findById(Uuid::fromString($command->authorId))
            ?? throw new \RuntimeException('Author not found: '.$command->authorId);

        $comment = new Comment($ticket, $author, $command->body, $command->isInternal);

        if (!$command->isInternal
            && UserRole::REPORTER !== $author->getRole()
            && null === $ticket->getRespondedAt()
        ) {
            $ticket->markResponded();
            $this->ticketRepo->save($ticket);
        }

        $this->commentRepo->save($comment);

        return $comment;
    }
}
