<?php

declare(strict_types=1);

namespace App\Ticket\Application\Command;

final class AddComment
{
    public function __construct(
        public readonly string $ticketId,
        public readonly string $authorId,
        public readonly string $body,
        public readonly bool $isInternal,
    ) {
    }
}
