<?php

declare(strict_types=1);

namespace App\Ticket\Application\Command;

use App\Ticket\Domain\Enum\TicketStatus;
use App\Ticket\Domain\Exception\InvalidStatusTransitionException;
use App\Ticket\Domain\Exception\TicketNotFoundException;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Uid\Uuid;

final class ChangeTicketStatusHandler
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * @throws OptimisticLockException          when concurrent update is detected (version mismatch)
     * @throws TicketNotFoundException
     * @throws InvalidStatusTransitionException
     */
    public function __invoke(ChangeTicketStatus $command): void
    {
        $ticket = $this->ticketRepo->findById(Uuid::fromString($command->ticketId))
            ?? throw new TicketNotFoundException($command->ticketId);

        // Verify the client's version matches the DB version before making any changes.
        // Throws OptimisticLockException if another request already modified the ticket.
        $this->em->lock($ticket, \Doctrine\DBAL\LockMode::OPTIMISTIC, $command->version);

        $newStatus = TicketStatus::from($command->newStatus);

        if (!$ticket->getStatus()->canTransitionTo($newStatus)) {
            throw new InvalidStatusTransitionException($ticket->getStatus(), $newStatus);
        }

        $ticket->changeStatus($newStatus);

        if (TicketStatus::RESOLVED === $newStatus) {
            $ticket->markResolved();
        }

        if (TicketStatus::IN_PROGRESS === $newStatus && null === $ticket->getRespondedAt()) {
            $ticket->markResponded();
        }

        $this->ticketRepo->save($ticket);
    }
}
