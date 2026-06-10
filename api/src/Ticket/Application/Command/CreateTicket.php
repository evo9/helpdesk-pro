<?php

declare(strict_types=1);

namespace App\Ticket\Application\Command;

final class CreateTicket
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $categoryId,
        public readonly string $priority,
        public readonly string $reporterId,
    ) {
    }
}
