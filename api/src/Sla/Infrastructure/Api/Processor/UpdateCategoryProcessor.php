<?php

declare(strict_types=1);

namespace App\Sla\Infrastructure\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Sla\Application\Command\UpdateCategory;
use App\Sla\Application\Command\UpdateCategoryHandler;
use App\Sla\Domain\Repository\CategoryRepositoryInterface;
use App\Sla\Infrastructure\Api\Provider\CategoryStateProvider;
use App\Sla\Infrastructure\Api\Resource\CategoryResource;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

/** @implements ProcessorInterface<CategoryResource, CategoryResource> */
final class UpdateCategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly UpdateCategoryHandler $handler,
        private readonly CategoryRepositoryInterface $categoryRepo,
        private readonly CategoryStateProvider $provider,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CategoryResource
    {
        if (!$this->security->isGranted('ROLE_MANAGER')) {
            throw new AccessDeniedException();
        }

        $categoryId = (string) ($uriVariables['id'] ?? '');

        /** @var CategoryResource $data */
        /** @var CategoryResource $previous */
        $previous = $context['previous_data'];

        ($this->handler)(new UpdateCategory(
            categoryId: $categoryId,
            name: $data->name !== $previous->name ? $data->name : null,
            description: $data->description,
            isActive: $data->isActive !== $previous->isActive ? $data->isActive : null,
        ));

        $category = $this->categoryRepo->findById(Uuid::fromString($categoryId))
            ?? throw new NotFoundHttpException('Category not found.');

        return $this->provider->toResource($category);
    }
}
