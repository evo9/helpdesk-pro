<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Query;

final class AgentWorkloadItem
{
    public function __construct(
        public readonly string $agentId,
        public readonly string $name,
        public readonly int $activeTickets,
        public readonly int $resolvedLast30d,
    ) {
    }
}
