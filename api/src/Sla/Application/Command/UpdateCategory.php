<?php

declare(strict_types=1);

namespace App\Sla\Application\Command;

final class UpdateCategory
{
    public function __construct(
        public readonly string $categoryId,
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?bool $isActive,
    ) {
    }
}
