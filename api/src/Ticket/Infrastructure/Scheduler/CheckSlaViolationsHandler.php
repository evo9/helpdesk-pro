<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Scheduler;

use App\Ticket\Domain\Enum\TicketStatus;
use App\Ticket\Domain\Repository\AuditLogRepositoryInterface;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Messenger\Message\SlaViolatedMessage;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final class CheckSlaViolationsHandler
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly AuditLogRepositoryInterface $auditLogRepo,
        private readonly MessageBusInterface $bus,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(CheckSlaViolationsMessage $message): void
    {
        $now = $this->clock->now();

        foreach ($this->ticketRepo->findSlaBreached() as $ticket) {
            $responseBreached = null !== $ticket->getResponseDueAt()
                && $ticket->getResponseDueAt() < $now
                && null === $ticket->getRespondedAt();

            $resolutionBreached = null !== $ticket->getResolutionDueAt()
                && $ticket->getResolutionDueAt() < $now
                && !\in_array($ticket->getStatus(), [TicketStatus::RESOLVED, TicketStatus::CLOSED], true);

            if ($responseBreached && !$this->auditLogRepo->hasSlaBreachRecorded($ticket, 'response')) {
                $this->bus->dispatch(new SlaViolatedMessage((string) $ticket->getId(), 'response'));
            }

            if ($resolutionBreached && !$this->auditLogRepo->hasSlaBreachRecorded($ticket, 'resolution')) {
                $this->bus->dispatch(new SlaViolatedMessage((string) $ticket->getId(), 'resolution'));
            }
        }
    }
}
