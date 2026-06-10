<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Messenger\Handler;

use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Messenger\Message\TicketAssignedMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class SendTicketAssignedEmailHandler
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function __invoke(TicketAssignedMessage $message): void
    {
        $ticket = $this->ticketRepo->findById(Uuid::fromString($message->ticketId))
            ?? throw new \RuntimeException('Ticket not found: '.$message->ticketId);

        $assignee = $ticket->getAssignee();
        if (null === $assignee) {
            return;
        }

        $this->mailer->send(
            (new Email())
                ->from('HelpDesk Pro <noreply@helpdesk.local>')
                ->to($assignee->getEmail())
                ->subject('Ticket assigned to you: '.$ticket->getTitle())
                ->text(\sprintf(
                    "Hello %s,\n\nTicket \"%s\" has been assigned to you.\n\nPriority: %s\nReporter: %s\nTicket ID: %s\n\nPlease take action as soon as possible.",
                    $assignee->getFullName(),
                    $ticket->getTitle(),
                    $ticket->getPriority()->value,
                    $ticket->getReporter()->getFullName(),
                    $ticket->getId()->toString(),
                ))
        );
    }
}
