<?php

declare(strict_types=1);

namespace App\Ticket\Domain\Repository;

use App\Ticket\Domain\Entity\AuditLog;
use App\Ticket\Domain\Entity\Ticket;

interface AuditLogRepositoryInterface
{
    public function save(AuditLog $auditLog): void;

    /** @return AuditLog[] */
    public function findByTicketSortedDesc(Ticket $ticket): array;

    public function hasSlaBreachRecorded(Ticket $ticket, string $violationType): bool;
}
