<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket;

use App\Ticket\Domain\Enum\TicketStatus;
use PHPUnit\Framework\TestCase;

final class TicketStatusTransitionTest extends TestCase
{
    /** @return array<string, array{TicketStatus, TicketStatus, bool}> */
    public static function transitionProvider(): array
    {
        return [
            'open→in_progress allowed' => [TicketStatus::OPEN, TicketStatus::IN_PROGRESS, true],
            'open→pending denied' => [TicketStatus::OPEN, TicketStatus::PENDING, false],
            'open→resolved denied' => [TicketStatus::OPEN, TicketStatus::RESOLVED, false],
            'open→closed denied' => [TicketStatus::OPEN, TicketStatus::CLOSED, false],
            'open→open denied' => [TicketStatus::OPEN, TicketStatus::OPEN, false],
            'in_progress→pending allowed' => [TicketStatus::IN_PROGRESS, TicketStatus::PENDING, true],
            'in_progress→resolved allowed' => [TicketStatus::IN_PROGRESS, TicketStatus::RESOLVED, true],
            'in_progress→open denied' => [TicketStatus::IN_PROGRESS, TicketStatus::OPEN, false],
            'in_progress→closed denied' => [TicketStatus::IN_PROGRESS, TicketStatus::CLOSED, false],
            'pending→resolved allowed' => [TicketStatus::PENDING, TicketStatus::RESOLVED, true],
            'pending→in_progress allowed' => [TicketStatus::PENDING, TicketStatus::IN_PROGRESS, true],
            'pending→open denied' => [TicketStatus::PENDING, TicketStatus::OPEN, false],
            'pending→closed denied' => [TicketStatus::PENDING, TicketStatus::CLOSED, false],
            'resolved→closed allowed' => [TicketStatus::RESOLVED, TicketStatus::CLOSED, true],
            'resolved→open allowed' => [TicketStatus::RESOLVED, TicketStatus::OPEN, true],
            'resolved→in_progress denied' => [TicketStatus::RESOLVED, TicketStatus::IN_PROGRESS, false],
            'closed→open denied' => [TicketStatus::CLOSED, TicketStatus::OPEN, false],
            'closed→in_progress denied' => [TicketStatus::CLOSED, TicketStatus::IN_PROGRESS, false],
            'closed→resolved denied' => [TicketStatus::CLOSED, TicketStatus::RESOLVED, false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('transitionProvider')]
    public function testTransition(TicketStatus $from, TicketStatus $to, bool $allowed): void
    {
        $this->assertSame($allowed, $from->canTransitionTo($to));
    }
}
