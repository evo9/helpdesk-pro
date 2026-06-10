<?php

declare(strict_types=1);

namespace App\Sla\Application\Command;

use App\Sla\Domain\Repository\SlaPolicyRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class UpdateSlaPolicyHandler
{
    public function __construct(
        private readonly SlaPolicyRepositoryInterface $slaPolicyRepo,
    ) {
    }

    public function __invoke(UpdateSlaPolicy $command): void
    {
        $policy = $this->slaPolicyRepo->findById(Uuid::fromString($command->policyId))
            ?? throw new NotFoundHttpException('SLA policy not found.');

        $policy->update($command->responseHours, $command->resolutionHours);
        $this->slaPolicyRepo->save($policy);
    }
}
