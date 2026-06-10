<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Messenger\Handler;

use App\Ticket\Domain\Entity\AuditLog;
use App\Ticket\Domain\Repository\AuditLogRepositoryInterface;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Messenger\Message\SlaViolatedMessage;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final class HandleSlaViolatedHandler
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly UserRepositoryInterface $userRepo,
        private readonly AuditLogRepositoryInterface $auditLogRepo,
        private readonly MailerInterface $mailer,
    ) {
    }

    public function __invoke(SlaViolatedMessage $message): void
    {
        $ticket = $this->ticketRepo->findById(Uuid::fromString($message->ticketId))
            ?? throw new \RuntimeException('Ticket not found: '.$message->ticketId);

        $actor = $ticket->getAssignee() ?? $ticket->getReporter();

        $this->auditLogRepo->save(new AuditLog(
            $ticket,
            $actor,
            'ticket.sla_breached',
            ['type' => $message->violationType],
        ));

        $body = \sprintf(
            "SLA violation alert!\n\nTicket: %s\nViolation type: %s\nPriority: %s\nReporter: %s\nTicket ID: %s",
            $ticket->getTitle(),
            $message->violationType,
            $ticket->getPriority()->value,
            $ticket->getReporter()->getFullName(),
            $ticket->getId()->toString(),
        );

        $assignee = $ticket->getAssignee();
        if (null !== $assignee) {
            $this->mailer->send(
                (new Email())
                    ->from('HelpDesk Pro <noreply@helpdesk.local>')
                    ->to($assignee->getEmail())
                    ->subject(\sprintf('SLA %s violation: %s', $message->violationType, $ticket->getTitle()))
                    ->text("Hello {$assignee->getFullName()},\n\n".$body)
            );
        }

        foreach ($this->userRepo->findManagers() as $manager) {
            $this->mailer->send(
                (new Email())
                    ->from('HelpDesk Pro <noreply@helpdesk.local>')
                    ->to($manager->getEmail())
                    ->subject(\sprintf('[SLA Alert] %s violation: %s', $message->violationType, $ticket->getTitle()))
                    ->text("Hello {$manager->getFullName()},\n\n".$body)
            );
        }
    }
}
