<?php

declare(strict_types=1);

namespace App\Sla\Application\Command;

final class CreateSlaPolicy
{
    public function __construct(
        public readonly string $categoryId,
        public readonly string $priority,
        public readonly int $responseHours,
        public readonly int $resolutionHours,
    ) {
    }
}
