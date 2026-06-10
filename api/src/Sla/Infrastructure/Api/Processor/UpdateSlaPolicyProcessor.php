<?php

declare(strict_types=1);

namespace App\Sla\Infrastructure\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Sla\Application\Command\UpdateSlaPolicy;
use App\Sla\Application\Command\UpdateSlaPolicyHandler;
use App\Sla\Domain\Repository\SlaPolicyRepositoryInterface;
use App\Sla\Infrastructure\Api\Provider\SlaPolicyStateProvider;
use App\Sla\Infrastructure\Api\Resource\SlaPolicyResource;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

/** @implements ProcessorInterface<SlaPolicyResource, SlaPolicyResource> */
final class UpdateSlaPolicyProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly UpdateSlaPolicyHandler $handler,
        private readonly SlaPolicyRepositoryInterface $slaPolicyRepo,
        private readonly SlaPolicyStateProvider $provider,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SlaPolicyResource
    {
        if (!$this->security->isGranted('ROLE_MANAGER')) {
            throw new AccessDeniedException();
        }

        $policyId = (string) ($uriVariables['id'] ?? '');

        /* @var SlaPolicyResource $data */
        ($this->handler)(new UpdateSlaPolicy(
            policyId: $policyId,
            responseHours: $data->responseHours ?? 0,
            resolutionHours: $data->resolutionHours ?? 0,
        ));

        $policy = $this->slaPolicyRepo->findById(Uuid::fromString($policyId))
            ?? throw new NotFoundHttpException('SLA policy not found.');

        return $this->provider->toResource($policy);
    }
}
