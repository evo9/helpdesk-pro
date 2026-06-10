<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Messenger\Message;

final class TicketCreatedMessage
{
    public function __construct(
        public readonly string $ticketId,
    ) {
    }
}
