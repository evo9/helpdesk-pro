<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket\Domain\Enum;

use App\Ticket\Domain\Enum\TicketStatus;
use PHPUnit\Framework\TestCase;

final class TicketStatusTest extends TestCase
{
    public function testCasesHaveCorrectValues(): void
    {
        $this->assertSame('open', TicketStatus::OPEN->value);
        $this->assertSame('in_progress', TicketStatus::IN_PROGRESS->value);
        $this->assertSame('pending', TicketStatus::PENDING->value);
        $this->assertSame('resolved', TicketStatus::RESOLVED->value);
        $this->assertSame('closed', TicketStatus::CLOSED->value);
    }

    public function testIsTerminalReturnsTrueForClosedStatus(): void
    {
        $this->assertTrue(TicketStatus::CLOSED->isTerminal());
        $this->assertFalse(TicketStatus::OPEN->isTerminal());
        $this->assertFalse(TicketStatus::RESOLVED->isTerminal());
    }

    public function testCanReopenReturnsTrueOnlyForResolved(): void
    {
        $this->assertTrue(TicketStatus::RESOLVED->canReopen());
        $this->assertFalse(TicketStatus::OPEN->canReopen());
        $this->assertFalse(TicketStatus::CLOSED->canReopen());
    }
}
