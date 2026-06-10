<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket\Infrastructure\Messenger\Handler;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Messenger\Handler\SendTicketAssignedEmailHandler;
use App\Ticket\Infrastructure\Messenger\Message\TicketAssignedMessage;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class SendTicketAssignedEmailHandlerTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $mailer;
    private \PHPUnit\Framework\MockObject\Stub $ticketRepo;
    private SendTicketAssignedEmailHandler $handler;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->ticketRepo = $this->createStub(TicketRepositoryInterface::class);
        $this->handler = new SendTicketAssignedEmailHandler($this->ticketRepo, $this->mailer);
    }

    public function testSendsEmailToAssignedAgent(): void
    {
        $reporter = new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);
        $agent = new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT);
        $ticket->assignTo($agent);

        $this->ticketRepo->method('findById')->willReturn($ticket);

        $sentTo = null;
        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(static function (Email $email) use (&$sentTo): void {
                $sentTo = $email->getTo()[0]->getAddress();
            });

        ($this->handler)(new TicketAssignedMessage($ticket->getId()->toString()));

        $this->assertSame('agent@example.com', $sentTo);
    }

    public function testSkipsEmailWhenNoAssignee(): void
    {
        $reporter = new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->ticketRepo->method('findById')->willReturn($ticket);
        $this->mailer->expects($this->never())->method('send');

        ($this->handler)(new TicketAssignedMessage($ticket->getId()->toString()));
    }

    private function makeTicket(User $reporter): Ticket
    {
        return new Ticket('Test ticket', 'Description', TicketPriority::MEDIUM, new Category('IT'), $reporter, null, null, null);
    }
}
