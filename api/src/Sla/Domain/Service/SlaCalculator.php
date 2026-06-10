<?php

declare(strict_types=1);

namespace App\Sla\Domain\Service;

use App\Sla\Domain\Entity\SlaPolicy;

final class SlaCalculator
{
    public function calculate(SlaPolicy $policy, \DateTimeImmutable $createdAt): SlaDeadlines
    {
        return new SlaDeadlines(
            responseDueAt: $createdAt->modify(\sprintf('+%d hours', $policy->getResponseHours())),
            resolutionDueAt: $createdAt->modify(\sprintf('+%d hours', $policy->getResolutionHours())),
        );
    }
}
