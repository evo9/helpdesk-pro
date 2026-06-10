<?php

declare(strict_types=1);

namespace App\Sla\Application\Command;

final class CreateCategory
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
    ) {
    }
}
