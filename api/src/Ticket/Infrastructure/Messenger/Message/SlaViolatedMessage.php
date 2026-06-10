<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Messenger\Message;

final class SlaViolatedMessage
{
    public function __construct(
        public readonly string $ticketId,
        public readonly string $violationType, // 'response' | 'resolution'
    ) {
    }
}
