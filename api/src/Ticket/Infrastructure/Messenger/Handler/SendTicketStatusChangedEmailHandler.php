<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Messenger\Handler;

use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Messenger\Message\TicketStatusChangedMessage;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class SendTicketStatusChangedEmailHandler
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function __invoke(TicketStatusChangedMessage $message): void
    {
        $ticket = $this->ticketRepo->findById(Uuid::fromString($message->ticketId))
            ?? throw new \RuntimeException('Ticket not found: '.$message->ticketId);

        $reporter = $ticket->getReporter();

        $this->mailer->send(
            (new Email())
                ->from('HelpDesk Pro <noreply@helpdesk.local>')
                ->to($reporter->getEmail())
                ->subject(\sprintf('Ticket status changed to %s: %s', $message->newStatus, $ticket->getTitle()))
                ->text(\sprintf(
                    "Hello %s,\n\nThe status of your ticket \"%s\" has been updated.\n\nPrevious status: %s\nNew status: %s\nTicket ID: %s",
                    $reporter->getFullName(),
                    $ticket->getTitle(),
                    $message->oldStatus,
                    $message->newStatus,
                    $ticket->getId()->toString(),
                ))
        );
    }
}
