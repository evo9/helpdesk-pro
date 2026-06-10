<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket\Infrastructure\Scheduler;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Repository\AuditLogRepositoryInterface;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Messenger\Message\SlaViolatedMessage;
use App\Ticket\Infrastructure\Scheduler\CheckSlaViolationsHandler;
use App\Ticket\Infrastructure\Scheduler\CheckSlaViolationsMessage;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class CheckSlaViolationsHandlerTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\Stub $ticketRepo;
    private \PHPUnit\Framework\MockObject\Stub $auditLogRepo;
    private \PHPUnit\Framework\MockObject\Stub $clock;

    protected function setUp(): void
    {
        $this->ticketRepo = $this->createStub(TicketRepositoryInterface::class);
        $this->auditLogRepo = $this->createStub(AuditLogRepositoryInterface::class);
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-06-10 12:00:00'));
    }

    public function testDispatchesResolutionViolationForBreachedTicket(): void
    {
        $ticket = $this->makeTicket(resolutionDueAt: new \DateTimeImmutable('2026-06-10 10:00:00'));

        $this->ticketRepo->method('findSlaBreached')->willReturn([$ticket]);
        $this->auditLogRepo->method('hasSlaBreachRecorded')->willReturn(false);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static fn (object $msg) => $msg instanceof SlaViolatedMessage
                && $msg->ticketId === $ticket->getId()->toString()
                && 'resolution' === $msg->violationType))
            ->willReturn(new Envelope(new \stdClass()));

        $this->makeHandler($bus)(new CheckSlaViolationsMessage());
    }

    public function testDispatchesResponseViolationWhenResponseDueAndNotResponded(): void
    {
        $ticket = $this->makeTicket(responseDueAt: new \DateTimeImmutable('2026-06-10 10:00:00'));

        $this->ticketRepo->method('findSlaBreached')->willReturn([$ticket]);
        $this->auditLogRepo->method('hasSlaBreachRecorded')->willReturn(false);

        $dispatched = [];
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('dispatch')
            ->willReturnCallback(static function (object $msg) use (&$dispatched): Envelope {
                $dispatched[] = $msg;

                return new Envelope($msg);
            });

        $this->makeHandler($bus)(new CheckSlaViolationsMessage());

        $types = array_map(static fn (SlaViolatedMessage $m) => $m->violationType, array_filter($dispatched, static fn ($m) => $m instanceof SlaViolatedMessage));
        $this->assertContains('response', $types);
    }

    public function testSkipsAlreadyRecordedResolutionViolation(): void
    {
        $ticket = $this->makeTicket(resolutionDueAt: new \DateTimeImmutable('2026-06-10 10:00:00'));

        $this->ticketRepo->method('findSlaBreached')->willReturn([$ticket]);
        $this->auditLogRepo->method('hasSlaBreachRecorded')->willReturn(true);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $this->makeHandler($bus)(new CheckSlaViolationsMessage());
    }

    public function testDispatchesBothTypesWhenBothBreached(): void
    {
        $ticket = $this->makeTicket(
            responseDueAt: new \DateTimeImmutable('2026-06-10 09:00:00'),
            resolutionDueAt: new \DateTimeImmutable('2026-06-10 10:00:00'),
        );

        $this->ticketRepo->method('findSlaBreached')->willReturn([$ticket]);
        $this->auditLogRepo->method('hasSlaBreachRecorded')->willReturn(false);

        $dispatched = [];
        $bus = $this->createStub(MessageBusInterface::class);
        $bus->method('dispatch')
            ->willReturnCallback(static function (object $msg) use (&$dispatched): Envelope {
                $dispatched[] = $msg;

                return new Envelope($msg);
            });

        $this->makeHandler($bus)(new CheckSlaViolationsMessage());

        $types = array_map(static fn (SlaViolatedMessage $m) => $m->violationType, $dispatched);
        $this->assertContains('response', $types);
        $this->assertContains('resolution', $types);
    }

    public function testDoesNotDispatchWhenNoBreachedTickets(): void
    {
        $this->ticketRepo->method('findSlaBreached')->willReturn([]);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->never())->method('dispatch');

        $this->makeHandler($bus)(new CheckSlaViolationsMessage());
    }

    private function makeHandler(?MessageBusInterface $bus = null): CheckSlaViolationsHandler
    {
        return new CheckSlaViolationsHandler(
            $this->ticketRepo,
            $this->auditLogRepo,
            $bus ?? $this->createStub(MessageBusInterface::class),
            $this->clock,
        );
    }

    private function makeTicket(
        ?\DateTimeImmutable $responseDueAt = null,
        ?\DateTimeImmutable $resolutionDueAt = null,
    ): Ticket {
        $reporter = new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);

        return new Ticket('Test', 'Desc', TicketPriority::MEDIUM, new Category('IT'), $reporter, null, $responseDueAt, $resolutionDueAt);
    }
}
