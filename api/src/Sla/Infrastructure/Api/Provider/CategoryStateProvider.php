<?php

declare(strict_types=1);

namespace App\Sla\Infrastructure\Api\Provider;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Repository\CategoryRepositoryInterface;
use App\Sla\Infrastructure\Api\Resource\CategoryResource;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

/** @implements ProviderInterface<CategoryResource> */
final class CategoryStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepo,
        private readonly Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array
    {
        if (!$this->security->isGranted('ROLE_REPORTER')) {
            throw new AccessDeniedException();
        }

        if ($operation instanceof CollectionOperationInterface) {
            $isManager = $this->isManager();
            $categories = $isManager
                ? $this->categoryRepo->findAll()
                : $this->categoryRepo->findAllActive();

            return array_map(fn (Category $c) => $this->toResource($c), $categories);
        }

        $category = $this->categoryRepo->findById(Uuid::fromString((string) ($uriVariables['id'] ?? '')));
        if (null === $category) {
            throw new NotFoundHttpException('Category not found.');
        }

        return $this->toResource($category);
    }

    public function toResource(Category $category): CategoryResource
    {
        $resource = new CategoryResource();
        $resource->id = (string) $category->getId();
        $resource->name = $category->getName();
        $resource->description = $category->getDescription();
        $resource->isActive = $category->isActive();

        return $resource;
    }

    private function isManager(): bool
    {
        $user = $this->security->getUser();

        return $user instanceof User && UserRole::MANAGER === $user->getRole();
    }
}
