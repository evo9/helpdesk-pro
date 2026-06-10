<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Messenger\Message;

final class TicketStatusChangedMessage
{
    public function __construct(
        public readonly string $ticketId,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {
    }
}
