<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket\Domain\Entity;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Entity\SlaPolicy;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Enum\TicketStatus;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;

final class TicketTest extends TestCase
{
    private User $reporter;
    private Category $category;
    private SlaPolicy $policy;

    protected function setUp(): void
    {
        $this->reporter = new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);
        $this->category = new Category('Hardware');
        $this->policy = new SlaPolicy($this->category, TicketPriority::HIGH, 4, 24);
    }

    public function testCreatesTicketWithRequiredFields(): void
    {
        $responseDueAt = new \DateTimeImmutable('+4 hours');
        $resolutionDueAt = new \DateTimeImmutable('+24 hours');

        $ticket = new Ticket(
            title: 'Monitor not working',
            description: 'The display shows nothing after startup',
            priority: TicketPriority::HIGH,
            category: $this->category,
            reporter: $this->reporter,
            slaPolicy: $this->policy,
            responseDueAt: $responseDueAt,
            resolutionDueAt: $resolutionDueAt,
        );

        $this->assertSame('Monitor not working', $ticket->getTitle());
        $this->assertSame('The display shows nothing after startup', $ticket->getDescription());
        $this->assertSame(TicketPriority::HIGH, $ticket->getPriority());
        $this->assertSame($this->category, $ticket->getCategory());
        $this->assertSame($this->reporter, $ticket->getReporter());
        $this->assertSame(TicketStatus::OPEN, $ticket->getStatus());
        $this->assertNull($ticket->getAssignee());
        $this->assertSame($responseDueAt, $ticket->getResponseDueAt());
        $this->assertSame($resolutionDueAt, $ticket->getResolutionDueAt());
        $this->assertNotNull($ticket->getId());
        $this->assertNotNull($ticket->getCreatedAt());
        $this->assertNotNull($ticket->getUpdatedAt());
    }

    public function testAssignAgentSetsAssignee(): void
    {
        $ticket = $this->createTicket();
        $agent = new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT);

        $ticket->assignTo($agent);

        $this->assertSame($agent, $ticket->getAssignee());
    }

    public function testChangeStatusUpdatesStatus(): void
    {
        $ticket = $this->createTicket();

        $ticket->changeStatus(TicketStatus::IN_PROGRESS);

        $this->assertSame(TicketStatus::IN_PROGRESS, $ticket->getStatus());
    }

    public function testMarkRespondedSetsRespondedAt(): void
    {
        $ticket = $this->createTicket();
        $this->assertNull($ticket->getRespondedAt());

        $ticket->markResponded();

        $this->assertNotNull($ticket->getRespondedAt());
    }

    public function testMarkResolvedSetsResolvedAt(): void
    {
        $ticket = $this->createTicket();

        $ticket->changeStatus(TicketStatus::RESOLVED);
        $ticket->markResolved();

        $this->assertNotNull($ticket->getResolvedAt());
    }

    private function createTicket(): Ticket
    {
        return new Ticket(
            title: 'Test ticket',
            description: 'Description',
            priority: TicketPriority::MEDIUM,
            category: $this->category,
            reporter: $this->reporter,
            slaPolicy: $this->policy,
            responseDueAt: new \DateTimeImmutable('+4 hours'),
            resolutionDueAt: new \DateTimeImmutable('+24 hours'),
        );
    }
}
