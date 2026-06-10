<?php

declare(strict_types=1);

namespace App\Ticket\Domain\Exception;

final class TicketNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(\sprintf('Ticket "%s" not found.', $id));
    }
}
