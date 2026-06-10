<?php

declare(strict_types=1);

namespace App\Sla\Infrastructure\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Sla\Domain\Repository\SlaPolicyRepositoryInterface;
use App\Sla\Infrastructure\Api\Resource\SlaPolicyResource;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

/** @implements ProcessorInterface<SlaPolicyResource, null> */
final class DeleteSlaPolicyProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly SlaPolicyRepositoryInterface $slaPolicyRepo,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (!$this->security->isGranted('ROLE_MANAGER')) {
            throw new AccessDeniedException();
        }

        $policyId = (string) ($uriVariables['id'] ?? '');

        $policy = $this->slaPolicyRepo->findById(Uuid::fromString($policyId))
            ?? throw new NotFoundHttpException('SLA policy not found.');

        $this->slaPolicyRepo->remove($policy);

        return null;
    }
}
