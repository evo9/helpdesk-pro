<?php

declare(strict_types=1);

namespace App\Ticket\Domain\Enum;

enum TicketStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case PENDING = 'pending';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';

    public function isTerminal(): bool
    {
        return self::CLOSED === $this;
    }

    public function canReopen(): bool
    {
        return self::RESOLVED === $this;
    }

    public function canTransitionTo(self $new): bool
    {
        return match ($this) {
            self::OPEN => self::IN_PROGRESS === $new,
            self::IN_PROGRESS => \in_array($new, [self::PENDING, self::RESOLVED], true),
            self::PENDING => \in_array($new, [self::IN_PROGRESS, self::RESOLVED], true),
            self::RESOLVED => \in_array($new, [self::CLOSED, self::OPEN], true),
            self::CLOSED => false,
        };
    }
}
