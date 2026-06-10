<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket\Infrastructure\Messenger\Handler;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Messenger\Handler\SendTicketCreatedEmailHandler;
use App\Ticket\Infrastructure\Messenger\Message\TicketCreatedMessage;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class SendTicketCreatedEmailHandlerTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $mailer;
    private \PHPUnit\Framework\MockObject\Stub $ticketRepo;
    private \PHPUnit\Framework\MockObject\Stub $userRepo;
    private SendTicketCreatedEmailHandler $handler;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->ticketRepo = $this->createStub(TicketRepositoryInterface::class);
        $this->userRepo = $this->createStub(UserRepositoryInterface::class);
        $this->handler = new SendTicketCreatedEmailHandler(
            $this->ticketRepo,
            $this->userRepo,
            $this->mailer,
        );
    }

    public function testSendsConfirmationEmailToReporter(): void
    {
        $reporter = new User('reporter@example.com', 'hash', 'Reporter Name', UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->ticketRepo->method('findById')->willReturn($ticket);
        $this->userRepo->method('findActiveAgents')->willReturn([]);

        $sentEmails = [];
        $this->mailer
            ->expects($this->atLeastOnce())
            ->method('send')
            ->willReturnCallback(static function (Email $email) use (&$sentEmails): void {
                $sentEmails[] = $email;
            });

        ($this->handler)(new TicketCreatedMessage($ticket->getId()->toString()));

        $recipients = array_merge(...array_map(static fn (Email $e) => array_map(static fn ($a) => $a->getAddress(), $e->getTo()), $sentEmails));
        $this->assertContains('reporter@example.com', $recipients);
    }

    public function testSendsNotificationEmailToEachActiveAgent(): void
    {
        $reporter = new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $agent1 = new User('agent1@example.com', 'hash', 'Agent One', UserRole::AGENT);
        $agent2 = new User('agent2@example.com', 'hash', 'Agent Two', UserRole::AGENT);

        $this->ticketRepo->method('findById')->willReturn($ticket);
        $this->userRepo->method('findActiveAgents')->willReturn([$agent1, $agent2]);

        $sentEmails = [];
        $this->mailer
            ->expects($this->atLeast(2))
            ->method('send')
            ->willReturnCallback(static function (Email $email) use (&$sentEmails): void {
                $sentEmails[] = $email;
            });

        ($this->handler)(new TicketCreatedMessage($ticket->getId()->toString()));

        $recipients = array_merge(...array_map(static fn (Email $e) => array_map(static fn ($a) => $a->getAddress(), $e->getTo()), $sentEmails));
        $this->assertContains('agent1@example.com', $recipients);
        $this->assertContains('agent2@example.com', $recipients);
    }

    public function testDoesNotFailWhenNoAgentsExist(): void
    {
        $reporter = new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);

        $this->ticketRepo->method('findById')->willReturn($ticket);
        $this->userRepo->method('findActiveAgents')->willReturn([]);

        $this->mailer->expects($this->once())->method('send');

        ($this->handler)(new TicketCreatedMessage($ticket->getId()->toString()));
    }

    private function makeTicket(User $reporter): Ticket
    {
        return new Ticket('Test ticket', 'Description', TicketPriority::MEDIUM, new Category('IT'), $reporter, null, null, null);
    }
}
