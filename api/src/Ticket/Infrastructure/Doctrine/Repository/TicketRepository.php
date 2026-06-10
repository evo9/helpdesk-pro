<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Doctrine\Repository;

use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Enum\TicketStatus;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository implements TicketRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function findById(Uuid $id): ?Ticket
    {
        return $this->find($id);
    }

    public function findByAssignee(User $agent): array
    {
        return $this->findBy(['assignee' => $agent]);
    }

    public function findByReporter(User $reporter): array
    {
        return $this->findBy(['reporter' => $reporter]);
    }

    public function findUnassigned(): array
    {
        return $this->findBy(['assignee' => null]);
    }

    public function findByStatus(TicketStatus $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    public function findSlaBreached(): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('t')
            ->where('(t.responseDueAt < :now AND t.respondedAt IS NULL) OR (t.resolutionDueAt < :now AND t.status NOT IN (:terminal))')
            ->setParameter('now', $now)
            ->setParameter('terminal', [TicketStatus::RESOLVED, TicketStatus::CLOSED])
            ->getQuery()
            ->getResult();
    }

    public function save(Ticket $ticket): void
    {
        $this->getEntityManager()->persist($ticket);
        $this->getEntityManager()->flush();
    }

    public function remove(Ticket $ticket): void
    {
        $this->getEntityManager()->remove($ticket);
        $this->getEntityManager()->flush();
    }
}
