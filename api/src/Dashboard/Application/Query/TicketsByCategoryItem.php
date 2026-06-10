<?php

declare(strict_types=1);

namespace App\Dashboard\Application\Query;

final class TicketsByCategoryItem
{
    public function __construct(
        public readonly string $categoryId,
        public readonly string $categoryName,
        public readonly int $count,
    ) {
    }
}
