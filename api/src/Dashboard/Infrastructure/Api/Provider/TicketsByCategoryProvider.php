<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dashboard\Application\Query\TicketsByCategoryItem;
use App\Dashboard\Infrastructure\Api\Resource\TicketsByCategoryResource;
use App\Dashboard\Infrastructure\Doctrine\Query\GetTicketsByCategoryHandler;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/** @implements ProviderInterface<TicketsByCategoryResource> */
final class TicketsByCategoryProvider implements ProviderInterface
{
    public function __construct(
        private readonly GetTicketsByCategoryHandler $handler,
        private readonly Security $security,
    ) {
    }

    /** @return TicketsByCategoryResource[] */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        if (!$this->security->isGranted('ROLE_MANAGER')) {
            throw new AccessDeniedException();
        }

        return array_map(
            static fn (TicketsByCategoryItem $item) => self::toResource($item),
            ($this->handler)(),
        );
    }

    private static function toResource(TicketsByCategoryItem $item): TicketsByCategoryResource
    {
        $resource = new TicketsByCategoryResource();
        $resource->categoryId = $item->categoryId;
        $resource->categoryName = $item->categoryName;
        $resource->count = $item->count;

        return $resource;
    }
}
