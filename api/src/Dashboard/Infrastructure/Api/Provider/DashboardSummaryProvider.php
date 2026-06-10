<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dashboard\Application\Query\GetDashboardSummaryHandler;
use App\Dashboard\Infrastructure\Api\Resource\DashboardSummaryResource;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/** @implements ProviderInterface<DashboardSummaryResource> */
final class DashboardSummaryProvider implements ProviderInterface
{
    public function __construct(
        private readonly GetDashboardSummaryHandler $handler,
        private readonly Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): DashboardSummaryResource
    {
        if (!$this->security->isGranted('ROLE_MANAGER')) {
            throw new AccessDeniedException();
        }

        $summary = ($this->handler)();

        $resource = new DashboardSummaryResource();
        $resource->statuses = $summary->statusCounts;
        $resource->slaBreachedToday = $summary->slaBreachedToday;

        return $resource;
    }
}
