<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket\Domain\Entity;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Entity\SlaPolicy;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\AuditLog;
use App\Ticket\Domain\Entity\Ticket;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;

final class AuditLogTest extends TestCase
{
    public function testCreatesAuditLogEntry(): void
    {
        $ticket = $this->createTicket();
        $actor = new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT);

        $log = new AuditLog(
            ticket: $ticket,
            actor: $actor,
            action: 'ticket.status_changed',
            payload: ['from' => 'open', 'to' => 'in_progress'],
        );

        $this->assertSame($ticket, $log->getTicket());
        $this->assertSame($actor, $log->getActor());
        $this->assertSame('ticket.status_changed', $log->getAction());
        $this->assertSame(['from' => 'open', 'to' => 'in_progress'], $log->getPayload());
        $this->assertNotNull($log->getId());
        $this->assertNotNull($log->getCreatedAt());
    }

    public function testCreatesLogWithEmptyPayload(): void
    {
        $ticket = $this->createTicket();
        $actor = new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT);

        $log = new AuditLog($ticket, $actor, 'ticket.created', []);

        $this->assertSame([], $log->getPayload());
    }

    private function createTicket(): Ticket
    {
        $reporter = new User('r@example.com', 'hash', 'Reporter', UserRole::REPORTER);
        $category = new Category('Hardware');
        $policy = new SlaPolicy($category, TicketPriority::MEDIUM, 4, 24);

        return new Ticket('Title', 'Desc', TicketPriority::MEDIUM, $category, $reporter, $policy,
            new \DateTimeImmutable('+4 hours'), new \DateTimeImmutable('+24 hours'));
    }
}
