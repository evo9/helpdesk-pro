<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket\Infrastructure\Messenger\Handler;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\AuditLog;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Repository\AuditLogRepositoryInterface;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Messenger\Handler\HandleSlaViolatedHandler;
use App\Ticket\Infrastructure\Messenger\Message\SlaViolatedMessage;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class HandleSlaViolatedHandlerTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\Stub $ticketRepo;
    private \PHPUnit\Framework\MockObject\Stub $userRepo;

    protected function setUp(): void
    {
        $this->ticketRepo = $this->createStub(TicketRepositoryInterface::class);
        $this->userRepo = $this->createStub(UserRepositoryInterface::class);
    }

    public function testPersistsAuditLogEntry(): void
    {
        $ticket = $this->makeTicketWithAssignee();
        $this->ticketRepo->method('findById')->willReturn($ticket);
        $this->userRepo->method('findManagers')->willReturn([]);

        $saved = null;
        $auditLogRepo = $this->createMock(AuditLogRepositoryInterface::class);
        $auditLogRepo->expects($this->once())->method('save')
            ->willReturnCallback(static function (AuditLog $log) use (&$saved): void {
                $saved = $log;
            });

        $this->makeHandler($auditLogRepo)(new SlaViolatedMessage($ticket->getId()->toString(), 'response'));

        $this->assertInstanceOf(AuditLog::class, $saved);
        $this->assertSame('ticket.sla_breached', $saved->getAction());
    }

    public function testAuditLogPayloadContainsViolationType(): void
    {
        $ticket = $this->makeTicketWithAssignee();
        $this->ticketRepo->method('findById')->willReturn($ticket);
        $this->userRepo->method('findManagers')->willReturn([]);

        $saved = null;
        $auditLogRepo = $this->createStub(AuditLogRepositoryInterface::class);
        $auditLogRepo->method('save')->willReturnCallback(static function (AuditLog $log) use (&$saved): void {
            $saved = $log;
        });

        $this->makeHandler($auditLogRepo)(new SlaViolatedMessage($ticket->getId()->toString(), 'resolution'));

        $this->assertInstanceOf(AuditLog::class, $saved);
        $this->assertSame('resolution', $saved->getPayload()['type']);
    }

    public function testSendsEmailToAssignee(): void
    {
        $ticket = $this->makeTicketWithAssignee();
        $this->ticketRepo->method('findById')->willReturn($ticket);
        $this->userRepo->method('findManagers')->willReturn([]);

        $recipients = [];
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willReturnCallback(static function (Email $email) use (&$recipients): void {
            foreach ($email->getTo() as $address) {
                $recipients[] = $address->getAddress();
            }
        });

        $this->makeHandler(mailer: $mailer)(new SlaViolatedMessage($ticket->getId()->toString(), 'response'));

        $this->assertContains('agent@example.com', $recipients);
    }

    public function testSendsEmailToManagers(): void
    {
        $ticket = $this->makeTicketWithAssignee();
        $manager = new User('manager@example.com', 'hash', 'Manager', UserRole::MANAGER);
        $this->ticketRepo->method('findById')->willReturn($ticket);
        $this->userRepo->method('findManagers')->willReturn([$manager]);

        $recipients = [];
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willReturnCallback(static function (Email $email) use (&$recipients): void {
            foreach ($email->getTo() as $address) {
                $recipients[] = $address->getAddress();
            }
        });

        $this->makeHandler(mailer: $mailer)(new SlaViolatedMessage($ticket->getId()->toString(), 'response'));

        $this->assertContains('manager@example.com', $recipients);
    }

    private function makeHandler(
        ?AuditLogRepositoryInterface $auditLogRepo = null,
        ?MailerInterface $mailer = null,
    ): HandleSlaViolatedHandler {
        return new HandleSlaViolatedHandler(
            $this->ticketRepo,
            $this->userRepo,
            $auditLogRepo ?? $this->createStub(AuditLogRepositoryInterface::class),
            $mailer ?? $this->createStub(MailerInterface::class),
        );
    }

    private function makeTicketWithAssignee(): Ticket
    {
        $reporter = new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);
        $ticket = new Ticket('Test ticket', 'Description', TicketPriority::MEDIUM, new Category('IT'), $reporter, null, null, null);
        $ticket->assignTo(new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT));

        return $ticket;
    }
}
