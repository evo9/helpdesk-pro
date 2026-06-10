<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Doctrine\Repository;

use App\Ticket\Domain\Entity\AuditLog;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Repository\AuditLogRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
class AuditLogRepository extends ServiceEntityRepository implements AuditLogRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function save(AuditLog $auditLog): void
    {
        $this->getEntityManager()->persist($auditLog);
        $this->getEntityManager()->flush();
    }

    public function findByTicketSortedDesc(Ticket $ticket): array
    {
        return $this->findBy(['ticket' => $ticket], ['createdAt' => 'DESC']);
    }

    public function hasSlaBreachRecorded(Ticket $ticket, string $violationType): bool
    {
        /** @var AuditLog[] $entries */
        $entries = $this->findBy(['ticket' => $ticket, 'action' => 'ticket.sla_breached']);

        foreach ($entries as $entry) {
            if (($entry->getPayload()['type'] ?? null) === $violationType) {
                return true;
            }
        }

        return false;
    }
}
