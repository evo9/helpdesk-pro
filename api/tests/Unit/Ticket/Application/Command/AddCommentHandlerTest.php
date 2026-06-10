<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket\Application\Command;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Application\Command\AddComment;
use App\Ticket\Application\Command\AddCommentHandler;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Repository\CommentRepositoryInterface;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class AddCommentHandlerTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\Stub $ticketRepo;
    private \PHPUnit\Framework\MockObject\Stub $userRepo;
    private \PHPUnit\Framework\MockObject\Stub $commentRepo;
    private AddCommentHandler $handler;

    protected function setUp(): void
    {
        $this->ticketRepo = $this->createStub(TicketRepositoryInterface::class);
        $this->userRepo = $this->createStub(UserRepositoryInterface::class);
        $this->commentRepo = $this->createStub(CommentRepositoryInterface::class);
        $this->handler = new AddCommentHandler($this->ticketRepo, $this->userRepo, $this->commentRepo);
    }

    public function testSetsRespondedAtOnFirstAgentComment(): void
    {
        $ticket = $this->makeTicket();
        $agent = new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT);

        $this->ticketRepo->method('findById')->willReturn($ticket);
        $this->userRepo->method('findById')->willReturn($agent);

        ($this->handler)(new AddComment($ticket->getId()->toString(), $agent->getId()->toString(), 'Working on it.', false));

        $this->assertNotNull($ticket->getRespondedAt());
    }

    public function testDoesNotOverwriteRespondedAtOnSubsequentAgentComment(): void
    {
        $ticket = $this->makeTicket();
        $ticket->markResponded();
        $originalRespondedAt = $ticket->getRespondedAt();

        $agent = new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT);
        $this->ticketRepo->method('findById')->willReturn($ticket);
        $this->userRepo->method('findById')->willReturn($agent);

        ($this->handler)(new AddComment($ticket->getId()->toString(), $agent->getId()->toString(), 'Follow-up comment.', false));

        $this->assertSame($originalRespondedAt, $ticket->getRespondedAt());
    }

    public function testDoesNotSetRespondedAtForInternalComment(): void
    {
        $ticket = $this->makeTicket();
        $agent = new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT);

        $this->ticketRepo->method('findById')->willReturn($ticket);
        $this->userRepo->method('findById')->willReturn($agent);

        ($this->handler)(new AddComment($ticket->getId()->toString(), $agent->getId()->toString(), 'Internal note.', true));

        $this->assertNull($ticket->getRespondedAt());
    }

    public function testDoesNotSetRespondedAtForReporterComment(): void
    {
        $ticket = $this->makeTicket();
        $reporter = new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);

        $this->ticketRepo->method('findById')->willReturn($ticket);
        $this->userRepo->method('findById')->willReturn($reporter);

        ($this->handler)(new AddComment($ticket->getId()->toString(), $reporter->getId()->toString(), 'Any updates?', false));

        $this->assertNull($ticket->getRespondedAt());
    }

    private function makeTicket(): Ticket
    {
        $reporter = new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);
        $category = new Category('IT');

        return new Ticket('Title', 'Desc', TicketPriority::MEDIUM, $category, $reporter, null, null, null);
    }
}
