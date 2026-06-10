<?php

declare(strict_types=1);

namespace App\Ticket\Application\Query;

use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Enum\TicketStatus;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\User\Domain\Enum\UserRole;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class GetTicketListHandler
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly UserRepositoryInterface $userRepo,
    ) {
    }

    /** @return Ticket[] */
    public function __invoke(GetTicketList $query): array
    {
        $requester = $this->userRepo->findById(Uuid::fromString($query->requesterId))
            ?? throw new \RuntimeException('Requester not found: '.$query->requesterId);

        $tickets = match ($query->requesterRole) {
            UserRole::REPORTER => $this->ticketRepo->findByReporter($requester),
            UserRole::AGENT => $this->mergeUnique(
                $this->ticketRepo->findByAssignee($requester),
                $this->ticketRepo->findUnassigned(),
            ),
            UserRole::MANAGER => $this->ticketRepo->findAll(),
        };

        if (null !== $query->status) {
            $status = TicketStatus::from($query->status);
            $tickets = array_values(array_filter($tickets, static fn (Ticket $t) => $t->getStatus() === $status));
        }

        if (null !== $query->priority) {
            $priority = TicketPriority::from($query->priority);
            $tickets = array_values(array_filter($tickets, static fn (Ticket $t) => $t->getPriority() === $priority));
        }

        return $tickets;
    }

    /**
     * @param Ticket[] $a
     * @param Ticket[] $b
     *
     * @return Ticket[]
     */
    private function mergeUnique(array $a, array $b): array
    {
        $seen = [];
        $result = [];
        foreach ([...$a, ...$b] as $ticket) {
            $id = (string) $ticket->getId();
            if (!isset($seen[$id])) {
                $seen[$id] = true;
                $result[] = $ticket;
            }
        }

        return $result;
    }
}
