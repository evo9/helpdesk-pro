<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Query;

use App\Dashboard\Domain\Repository\DashboardRepositoryInterface;

final class GetDashboardSummaryHandler
{
    public function __construct(
        private readonly DashboardRepositoryInterface $repository,
    ) {
    }

    public function __invoke(): DashboardSummary
    {
        return $this->repository->getSummary();
    }
}
