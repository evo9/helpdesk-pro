<?php

declare(strict_types=1);

namespace App\Sla\Domain\Repository;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Entity\SlaPolicy;
use App\Sla\Domain\Enum\TicketPriority;
use Symfony\Component\Uid\Uuid;

interface SlaPolicyRepositoryInterface
{
    public function findById(Uuid $id): ?SlaPolicy;

    /** @return SlaPolicy[] */
    public function findAll(): array;

    public function findByCategoryAndPriority(Category $category, TicketPriority $priority): ?SlaPolicy;

    /** @return SlaPolicy[] */
    public function findByCategory(Category $category): array;

    public function save(SlaPolicy $policy): void;

    public function remove(SlaPolicy $policy): void;
}
