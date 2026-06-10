<?php

declare(strict_types=1);

namespace App\Ticket\Domain\Exception;

use App\Ticket\Domain\Enum\TicketStatus;

final class InvalidStatusTransitionException extends \DomainException
{
    public function __construct(TicketStatus $from, TicketStatus $to)
    {
        parent::__construct(\sprintf(
            'Cannot transition from "%s" to "%s".',
            $from->value,
            $to->value,
        ));
    }
}
