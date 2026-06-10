<?php

declare(strict_types=1);

namespace App\Ticket\Application\Query;

use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class GetTicketDetailHandler
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
    ) {
    }

    public function __invoke(GetTicketDetail $query): ?Ticket
    {
        return $this->ticketRepo->findById(Uuid::fromString($query->ticketId));
    }
}
