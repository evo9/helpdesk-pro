<?php

declare(strict_types=1);

namespace App\Ticket\Application\Command;

use App\Ticket\Domain\Exception\TicketNotFoundException;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class AssignTicketHandler
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly UserRepositoryInterface $userRepo,
    ) {
    }

    public function __invoke(AssignTicket $command): void
    {
        $ticket = $this->ticketRepo->findById(Uuid::fromString($command->ticketId))
            ?? throw new TicketNotFoundException($command->ticketId);

        $agent = null;
        if (null !== $command->agentId) {
            $agent = $this->userRepo->findById(Uuid::fromString($command->agentId))
                ?? throw new \RuntimeException('Agent not found: '.$command->agentId);
        }

        $ticket->assignTo($agent);
        $this->ticketRepo->save($ticket);
    }
}
