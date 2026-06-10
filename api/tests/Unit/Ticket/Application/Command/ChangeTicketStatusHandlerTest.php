<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket\Application\Command;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Application\Command\ChangeTicketStatus;
use App\Ticket\Application\Command\ChangeTicketStatusHandler;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;

final class ChangeTicketStatusHandlerTest extends TestCase
{
    private ChangeTicketStatusHandler $handler;
    private \PHPUnit\Framework\MockObject\Stub $ticketRepo;

    protected function setUp(): void
    {
        $this->ticketRepo = $this->createStub(TicketRepositoryInterface::class);
        $this->handler = new ChangeTicketStatusHandler($this->ticketRepo);
    }

    public function testSetsRespondedAtOnFirstInProgressTransition(): void
    {
        $ticket = $this->makeOpenTicket();
        $this->ticketRepo->method('findById')->willReturn($ticket);

        ($this->handler)(new ChangeTicketStatus($ticket->getId()->toString(), 'in_progress'));

        $this->assertNotNull($ticket->getRespondedAt());
    }

    public function testDoesNotOverwriteRespondedAtWhenAlreadySet(): void
    {
        $ticket = $this->makeOpenTicket();
        $ticket->markResponded();
        $originalRespondedAt = $ticket->getRespondedAt();
        $this->ticketRepo->method('findById')->willReturn($ticket);

        ($this->handler)(new ChangeTicketStatus($ticket->getId()->toString(), 'in_progress'));

        $this->assertSame($originalRespondedAt, $ticket->getRespondedAt());
    }

    public function testDoesNotSetRespondedAtForNonInProgressTransition(): void
    {
        $ticket = $this->makeTicketInProgress();
        $this->ticketRepo->method('findById')->willReturn($ticket);

        ($this->handler)(new ChangeTicketStatus($ticket->getId()->toString(), 'pending'));

        $this->assertNull($ticket->getRespondedAt());
    }

    private function makeOpenTicket(): Ticket
    {
        $reporter = new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);
        $category = new Category('IT');

        return new Ticket('Title', 'Desc', TicketPriority::MEDIUM, $category, $reporter, null, null, null);
    }

    private function makeTicketInProgress(): Ticket
    {
        $ticket = $this->makeOpenTicket();
        $ticket->changeStatus(\App\Ticket\Domain\Enum\TicketStatus::IN_PROGRESS);

        return $ticket;
    }
}
