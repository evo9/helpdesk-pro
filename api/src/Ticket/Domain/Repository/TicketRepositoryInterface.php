<?php

declare(strict_types=1);

namespace App\Ticket\Domain\Repository;

use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Enum\TicketStatus;
use App\User\Domain\Entity\User;
use Symfony\Component\Uid\Uuid;

interface TicketRepositoryInterface
{
    public function findById(Uuid $id): ?Ticket;

    /** @return Ticket[] */
    public function findByAssignee(User $agent): array;

    /** @return Ticket[] */
    public function findByReporter(User $reporter): array;

    /** @return Ticket[] */
    public function findUnassigned(): array;

    /** @return Ticket[] */
    public function findByStatus(TicketStatus $status): array;

    /** @return Ticket[] */
    public function findSlaBreached(): array;

    /** @return Ticket[] */
    public function findAll(): array;

    public function save(Ticket $ticket): void;

    public function remove(Ticket $ticket): void;
}
