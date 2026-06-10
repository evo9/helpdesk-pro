<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Messenger\Handler;

use App\Ticket\Domain\Repository\CommentRepositoryInterface;
use App\Ticket\Infrastructure\Messenger\Message\CommentAddedMessage;
use App\User\Domain\Enum\UserRole;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class SendCommentAddedEmailHandler
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepo,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function __invoke(CommentAddedMessage $message): void
    {
        $comment = $this->commentRepo->findById(Uuid::fromString($message->commentId))
            ?? throw new \RuntimeException('Comment not found: '.$message->commentId);

        if ($comment->isInternal()) {
            return;
        }

        $ticket = $comment->getTicket();
        $author = $comment->getAuthor();
        $authorIsAgent = \in_array($author->getRole(), [UserRole::AGENT, UserRole::MANAGER], true);

        if ($authorIsAgent) {
            $recipient = $ticket->getReporter();
        } else {
            $recipient = $ticket->getAssignee();
        }

        if (null === $recipient) {
            return;
        }

        $this->mailer->send(
            (new Email())
                ->from('HelpDesk Pro <noreply@helpdesk.local>')
                ->to($recipient->getEmail())
                ->subject('New comment on ticket: '.$ticket->getTitle())
                ->text(\sprintf(
                    "Hello %s,\n\nA new comment was added to ticket \"%s\" by %s:\n\n---\n%s\n---\n\nTicket ID: %s",
                    $recipient->getFullName(),
                    $ticket->getTitle(),
                    $author->getFullName(),
                    $comment->getBody(),
                    $ticket->getId()->toString(),
                ))
        );
    }
}
