<?php

declare(strict_types=1);

namespace App\Sla\Infrastructure\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Sla\Application\Command\CreateSlaPolicy;
use App\Sla\Application\Command\CreateSlaPolicyHandler;
use App\Sla\Infrastructure\Api\Provider\SlaPolicyStateProvider;
use App\Sla\Infrastructure\Api\Resource\SlaPolicyResource;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/** @implements ProcessorInterface<SlaPolicyResource, SlaPolicyResource> */
final class CreateSlaPolicyProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CreateSlaPolicyHandler $handler,
        private readonly SlaPolicyStateProvider $provider,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SlaPolicyResource
    {
        if (!$this->security->isGranted('ROLE_MANAGER')) {
            throw new AccessDeniedException();
        }

        /** @var SlaPolicyResource $data */
        $policy = ($this->handler)(new CreateSlaPolicy(
            categoryId: basename($data->category ?? ''),
            priority: $data->priority ?? '',
            responseHours: $data->responseHours ?? 0,
            resolutionHours: $data->resolutionHours ?? 0,
        ));

        return $this->provider->toResource($policy);
    }
}
