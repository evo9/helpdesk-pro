<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Query;

final class DashboardSummary
{
    /** @param array<string, int> $statusCounts */
    public function __construct(
        public readonly array $statusCounts,
        public readonly int $slaBreachedToday,
    ) {
    }
}
