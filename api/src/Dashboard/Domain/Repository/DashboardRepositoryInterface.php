<?php

declare(strict_types=1);

namespace App\Dashboard\Domain\Repository;

use App\Dashboard\Application\Query\AgentWorkloadItem;
use App\Dashboard\Application\Query\DashboardSummary;
use App\Dashboard\Application\Query\TicketsByCategoryItem;

interface DashboardRepositoryInterface
{
    public function getSummary(): DashboardSummary;

    /** @return AgentWorkloadItem[] */
    public function getAgentWorkload(): array;

    /** @return TicketsByCategoryItem[] */
    public function getTicketsByCategory(): array;
}
