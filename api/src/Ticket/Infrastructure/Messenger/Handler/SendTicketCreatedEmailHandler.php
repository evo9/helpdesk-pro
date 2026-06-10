<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Messenger\Handler;

use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Messenger\Message\TicketCreatedMessage;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class SendTicketCreatedEmailHandler
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly UserRepositoryInterface $userRepo,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function __invoke(TicketCreatedMessage $message): void
    {
        $ticket = $this->ticketRepo->findById(Uuid::fromString($message->ticketId))
            ?? throw new \RuntimeException('Ticket not found: '.$message->ticketId);

        $reporter = $ticket->getReporter();

        $this->mailer->send(
            (new Email())
                ->from('HelpDesk Pro <noreply@helpdesk.local>')
                ->to($reporter->getEmail())
                ->subject('Your ticket has been created: '.$ticket->getTitle())
                ->text(\sprintf(
                    "Hello %s,\n\nYour ticket \"%s\" has been successfully created.\nTicket ID: %s\n\nWe will get back to you as soon as possible.",
                    $reporter->getFullName(),
                    $ticket->getTitle(),
                    $ticket->getId()->toString(),
                ))
        );

        foreach ($this->userRepo->findActiveAgents() as $agent) {
            $this->mailer->send(
                (new Email())
                    ->from('HelpDesk Pro <noreply@helpdesk.local>')
                    ->to($agent->getEmail())
                    ->subject('New ticket in queue: '.$ticket->getTitle())
                    ->text(\sprintf(
                        "Hello %s,\n\nA new ticket has been created and is waiting for assignment.\n\nTitle: %s\nPriority: %s\nReporter: %s\nTicket ID: %s",
                        $agent->getFullName(),
                        $ticket->getTitle(),
                        $ticket->getPriority()->value,
                        $reporter->getFullName(),
                        $ticket->getId()->toString(),
                    ))
            );
        }
    }
}
