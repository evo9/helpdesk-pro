<?php

declare(strict_types=1);

namespace App\Sla\Application\Command;

use App\Sla\Domain\Repository\CategoryRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class UpdateCategoryHandler
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepo,
    ) {
    }

    public function __invoke(UpdateCategory $command): void
    {
        $category = $this->categoryRepo->findById(Uuid::fromString($command->categoryId))
            ?? throw new NotFoundHttpException('Category not found.');

        if (null !== $command->name) {
            $category->rename($command->name, $command->description);
        }

        if (null !== $command->isActive) {
            $command->isActive ? $category->activate() : $category->deactivate();
        }

        $this->categoryRepo->save($category);
    }
}
