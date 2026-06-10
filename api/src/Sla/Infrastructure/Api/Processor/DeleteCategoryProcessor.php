<?php

declare(strict_types=1);

namespace App\Sla\Infrastructure\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Sla\Domain\Repository\CategoryRepositoryInterface;
use App\Sla\Infrastructure\Api\Resource\CategoryResource;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

/** @implements ProcessorInterface<CategoryResource, null> */
final class DeleteCategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepo,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (!$this->security->isGranted('ROLE_MANAGER')) {
            throw new AccessDeniedException();
        }

        $categoryId = (string) ($uriVariables['id'] ?? '');

        $category = $this->categoryRepo->findById(Uuid::fromString($categoryId))
            ?? throw new NotFoundHttpException('Category not found.');

        $this->categoryRepo->remove($category);

        return null;
    }
}
