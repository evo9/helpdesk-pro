<?php

declare(strict_types=1);

namespace App\Sla\Application\Command;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Repository\CategoryRepositoryInterface;

final class CreateCategoryHandler
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepo,
    ) {
    }

    public function __invoke(CreateCategory $command): Category
    {
        $category = new Category($command->name, $command->description);
        $this->categoryRepo->save($category);

        return $category;
    }
}
