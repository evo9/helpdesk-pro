<?php

declare(strict_types=1);

namespace App\Ticket\Application\Query;

final class GetTicketDetail
{
    public function __construct(
        public readonly string $ticketId,
    ) {
    }
}
