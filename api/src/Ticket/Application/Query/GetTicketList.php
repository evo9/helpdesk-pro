<?php

declare(strict_types=1);

namespace App\Ticket\Application\Query;

use App\User\Domain\Enum\UserRole;

final class GetTicketList
{
    public function __construct(
        public readonly string $requesterId,
        public readonly UserRole $requesterRole,
        public readonly ?string $status = null,
        public readonly ?string $priority = null,
    ) {
    }
}
