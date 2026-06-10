<?php

declare(strict_types=1);

namespace App\Sla\Application\Command;

final class UpdateSlaPolicy
{
    public function __construct(
        public readonly string $policyId,
        public readonly int $responseHours,
        public readonly int $resolutionHours,
    ) {
    }
}
