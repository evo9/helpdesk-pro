<?php

declare(strict_types=1);

namespace App\Sla\Infrastructure\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Sla\Application\Command\CreateCategory;
use App\Sla\Application\Command\CreateCategoryHandler;
use App\Sla\Infrastructure\Api\Provider\CategoryStateProvider;
use App\Sla\Infrastructure\Api\Resource\CategoryResource;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/** @implements ProcessorInterface<CategoryResource, CategoryResource> */
final class CreateCategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CreateCategoryHandler $handler,
        private readonly CategoryStateProvider $provider,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CategoryResource
    {
        if (!$this->security->isGranted('ROLE_MANAGER')) {
            throw new AccessDeniedException();
        }

        /** @var CategoryResource $data */
        $category = ($this->handler)(new CreateCategory(
            name: $data->name ?? '',
            description: $data->description,
        ));

        return $this->provider->toResource($category);
    }
}
