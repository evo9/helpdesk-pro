<?php

declare(strict_types=1);

namespace App\Sla\Infrastructure\Doctrine\Repository;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Entity\SlaPolicy;
use App\Sla\Domain\Enum\TicketPriority;
use App\Sla\Domain\Repository\SlaPolicyRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<SlaPolicy>
 */
class SlaPolicyRepository extends ServiceEntityRepository implements SlaPolicyRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SlaPolicy::class);
    }

    public function findById(Uuid $id): ?SlaPolicy
    {
        return $this->find($id);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function findByCategoryAndPriority(Category $category, TicketPriority $priority): ?SlaPolicy
    {
        return $this->findOneBy(['category' => $category, 'priority' => $priority]);
    }

    public function findByCategory(Category $category): array
    {
        return $this->findBy(['category' => $category]);
    }

    public function save(SlaPolicy $policy): void
    {
        $this->getEntityManager()->persist($policy);
        $this->getEntityManager()->flush();
    }

    public function remove(SlaPolicy $policy): void
    {
        $this->getEntityManager()->remove($policy);
        $this->getEntityManager()->flush();
    }
}
