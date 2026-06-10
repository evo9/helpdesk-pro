<?php

declare(strict_types=1);

namespace App\Sla\Domain\Service;

final readonly class SlaDeadlines
{
    public function __construct(
        public \DateTimeImmutable $responseDueAt,
        public \DateTimeImmutable $resolutionDueAt,
    ) {
    }
}
