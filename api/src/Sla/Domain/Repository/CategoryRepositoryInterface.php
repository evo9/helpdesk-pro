<?php

declare(strict_types=1);

namespace App\Sla\Domain\Repository;

use App\Sla\Domain\Entity\Category;
use Symfony\Component\Uid\Uuid;

interface CategoryRepositoryInterface
{
    public function findById(Uuid $id): ?Category;

    /** @return Category[] */
    public function findAllActive(): array;

    /** @return Category[] */
    public function findAll(): array;

    public function save(Category $category): void;

    public function remove(Category $category): void;
}
