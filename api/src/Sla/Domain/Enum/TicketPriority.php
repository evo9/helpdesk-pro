<?php

declare(strict_types=1);

namespace App\Sla\Domain\Enum;

enum TicketPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';
}
