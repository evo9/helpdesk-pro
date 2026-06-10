<?php

declare(strict_types=1);

namespace App\Tests\Integration\Ticket\Scheduler;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Infrastructure\Messenger\Message\SlaViolatedMessage;
use App\Ticket\Infrastructure\Scheduler\CheckSlaViolationsHandler;
use App\Ticket\Infrastructure\Scheduler\CheckSlaViolationsMessage;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class CheckSlaViolationsHandlerIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->em->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    public function testDispatchesSlaViolatedMessageForTicketWithBreachedResolutionSla(): void
    {
        $now = new \DateTimeImmutable();
        $clock = new MockClock($now);

        $reporter = new User('reporter@sla-test.local', 'hash', 'Reporter', UserRole::REPORTER);
        $this->em->persist($reporter);

        $category = new Category('IT Support');
        $this->em->persist($category);

        $ticket = new Ticket(
            'SLA breach ticket',
            'Description',
            TicketPriority::HIGH,
            $category,
            $reporter,
            null,
            null,
            new \DateTimeImmutable('-2 hours'), // resolution_due_at 2h ago
        );
        $this->em->persist($ticket);
        $this->em->flush();

        $handler = $this->buildHandler($clock);
        $handler(new CheckSlaViolationsMessage());

        /** @var InMemoryTransport $transport */
        $transport = static::getContainer()->get('messenger.transport.async');
        $envelopes = $transport->get();

        $messages = array_map(static fn ($e) => $e->getMessage(), $envelopes);
        $slaMessages = array_values(array_filter($messages, static fn ($m) => $m instanceof SlaViolatedMessage));

        $this->assertNotEmpty($slaMessages, 'Expected at least one SlaViolatedMessage in the queue');
        $this->assertSame($ticket->getId()->toString(), $slaMessages[0]->ticketId);
        $this->assertSame('resolution', $slaMessages[0]->violationType);
    }

    public function testDoesNotDuplicateViolationAlreadyInAuditLog(): void
    {
        $now = new \DateTimeImmutable();
        $clock = new MockClock($now);

        $reporter = new User('reporter2@sla-test.local', 'hash', 'Reporter', UserRole::REPORTER);
        $this->em->persist($reporter);

        $category = new Category('Network');
        $this->em->persist($category);

        $ticket = new Ticket(
            'Already breached ticket',
            'Description',
            TicketPriority::MEDIUM,
            $category,
            $reporter,
            null,
            null,
            new \DateTimeImmutable('-2 hours'),
        );
        $this->em->persist($ticket);

        $auditLog = new \App\Ticket\Domain\Entity\AuditLog($ticket, $reporter, 'ticket.sla_breached', ['type' => 'resolution']);
        $this->em->persist($auditLog);
        $this->em->flush();

        $handler = $this->buildHandler($clock);
        $handler(new CheckSlaViolationsMessage());

        /** @var InMemoryTransport $transport */
        $transport = static::getContainer()->get('messenger.transport.async');
        $slaMessages = array_values(array_filter(
            array_map(static fn ($e) => $e->getMessage(), $transport->get()),
            static fn ($m) => $m instanceof SlaViolatedMessage,
        ));

        $this->assertEmpty($slaMessages, 'Expected no SlaViolatedMessage since violation already recorded in AuditLog');
    }

    private function buildHandler(\Psr\Clock\ClockInterface $clock): CheckSlaViolationsHandler
    {
        $container = static::getContainer();

        return new CheckSlaViolationsHandler(
            $container->get(\App\Ticket\Domain\Repository\TicketRepositoryInterface::class),
            $container->get(\App\Ticket\Domain\Repository\AuditLogRepositoryInterface::class),
            $container->get(\Symfony\Component\Messenger\MessageBusInterface::class),
            $clock,
        );
    }
}
