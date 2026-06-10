<?php

declare(strict_types=1);

namespace App\Ticket\Application\Command;

final class AssignTicket
{
    public function __construct(
        public readonly string $ticketId,
        public readonly ?string $agentId,
    ) {
    }
}
