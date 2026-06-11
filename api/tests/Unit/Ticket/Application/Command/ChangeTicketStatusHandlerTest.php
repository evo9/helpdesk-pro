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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use PHPUnit\Framework\TestCase;

final class ChangeTicketStatusHandlerTest extends TestCase
{
    private ChangeTicketStatusHandler $handler;
    private \PHPUnit\Framework\MockObject\Stub $ticketRepo;
    private \PHPUnit\Framework\MockObject\Stub $em;

    protected function setUp(): void
    {
        $this->ticketRepo = $this->createStub(TicketRepositoryInterface::class);
        $this->em = $this->createStub(EntityManagerInterface::class);
        $this->handler = new ChangeTicketStatusHandler($this->ticketRepo, $this->em);
    }

    public function testSetsRespondedAtOnFirstInProgressTransition(): void
    {
        $ticket = $this->makeOpenTicket();
        $this->ticketRepo->method('findById')->willReturn($ticket);

        ($this->handler)(new ChangeTicketStatus($ticket->getId()->toString(), 'in_progress', 1));

        $this->assertNotNull($ticket->getRespondedAt());
    }

    public function testDoesNotOverwriteRespondedAtWhenAlreadySet(): void
    {
        $ticket = $this->makeOpenTicket();
        $ticket->markResponded();
        $originalRespondedAt = $ticket->getRespondedAt();
        $this->ticketRepo->method('findById')->willReturn($ticket);

        ($this->handler)(new ChangeTicketStatus($ticket->getId()->toString(), 'in_progress', 1));

        $this->assertSame($originalRespondedAt, $ticket->getRespondedAt());
    }

    public function testDoesNotSetRespondedAtForNonInProgressTransition(): void
    {
        $ticket = $this->makeTicketInProgress();
        $this->ticketRepo->method('findById')->willReturn($ticket);

        ($this->handler)(new ChangeTicketStatus($ticket->getId()->toString(), 'pending', 1));

        $this->assertNull($ticket->getRespondedAt());
    }

    public function testPropagatesOptimisticLockExceptionOnVersionMismatch(): void
    {
        $ticket = $this->makeOpenTicket();
        $this->ticketRepo->method('findById')->willReturn($ticket);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('lock')
            ->willThrowException(OptimisticLockException::lockFailed($ticket));

        $handler = new ChangeTicketStatusHandler($this->ticketRepo, $em);

        $this->expectException(OptimisticLockException::class);
        ($handler)(new ChangeTicketStatus($ticket->getId()->toString(), 'in_progress', 99));
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
