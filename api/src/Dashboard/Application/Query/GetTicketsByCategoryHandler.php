<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Query;

use App\Dashboard\Domain\Repository\DashboardRepositoryInterface;

final class GetTicketsByCategoryHandler
{
    public function __construct(
        private readonly DashboardRepositoryInterface $repository,
    ) {
    }

    /** @return TicketsByCategoryItem[] */
    public function __invoke(): array
    {
        return $this->repository->getTicketsByCategory();
    }
}
