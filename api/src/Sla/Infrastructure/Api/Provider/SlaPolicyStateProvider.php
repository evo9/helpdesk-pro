<?php

declare(strict_types=1);

namespace App\Sla\Infrastructure\Api\Provider;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Sla\Domain\Entity\SlaPolicy;
use App\Sla\Domain\Repository\SlaPolicyRepositoryInterface;
use App\Sla\Infrastructure\Api\Resource\SlaPolicyResource;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

/** @implements ProviderInterface<SlaPolicyResource> */
final class SlaPolicyStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly SlaPolicyRepositoryInterface $slaPolicyRepo,
        private readonly Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array
    {
        if (!$this->security->isGranted('ROLE_AGENT')) {
            throw new AccessDeniedException();
        }

        if ($operation instanceof CollectionOperationInterface) {
            return array_map(
                fn (SlaPolicy $p) => $this->toResource($p),
                $this->slaPolicyRepo->findAll(),
            );
        }

        $policy = $this->slaPolicyRepo->findById(Uuid::fromString((string) ($uriVariables['id'] ?? '')));
        if (null === $policy) {
            throw new NotFoundHttpException('SLA policy not found.');
        }

        return $this->toResource($policy);
    }

    public function toResource(SlaPolicy $policy): SlaPolicyResource
    {
        $resource = new SlaPolicyResource();
        $resource->id = (string) $policy->getId();
        $resource->category = '/api/categories/'.$policy->getCategory()->getId();
        $resource->categoryName = $policy->getCategory()->getName();
        $resource->priority = $policy->getPriority()->value;
        $resource->responseHours = $policy->getResponseHours();
        $resource->resolutionHours = $policy->getResolutionHours();

        return $resource;
    }
}
