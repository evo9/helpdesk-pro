<?php

declare(strict_types=1);

namespace App\Sla\Infrastructure\Doctrine\Repository;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Repository\CategoryRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository implements CategoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findById(Uuid $id): ?Category
    {
        return $this->find($id);
    }

    public function findAllActive(): array
    {
        return $this->findBy(['isActive' => true]);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function save(Category $category): void
    {
        $this->getEntityManager()->persist($category);
        $this->getEntityManager()->flush();
    }

    public function remove(Category $category): void
    {
        $this->getEntityManager()->remove($category);
        $this->getEntityManager()->flush();
    }
}
