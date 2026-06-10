<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dashboard\Application\Query\AgentWorkloadItem;
use App\Dashboard\Application\Query\GetAgentWorkloadHandler;
use App\Dashboard\Infrastructure\Api\Resource\AgentWorkloadResource;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/** @implements ProviderInterface<AgentWorkloadResource> */
final class AgentWorkloadProvider implements ProviderInterface
{
    public function __construct(
        private readonly GetAgentWorkloadHandler $handler,
        private readonly Security $security,
    ) {
    }

    /** @return AgentWorkloadResource[] */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        if (!$this->security->isGranted('ROLE_MANAGER')) {
            throw new AccessDeniedException();
        }

        return array_map(
            static fn (AgentWorkloadItem $item) => self::toResource($item),
            ($this->handler)(),
        );
    }

    private static function toResource(AgentWorkloadItem $item): AgentWorkloadResource
    {
        $resource = new AgentWorkloadResource();
        $resource->agentId = $item->agentId;
        $resource->name = $item->name;
        $resource->activeTickets = $item->activeTickets;
        $resource->resolvedLast30d = $item->resolvedLast30d;

        return $resource;
    }
}
