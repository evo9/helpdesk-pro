<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket\Infrastructure\Doctrine\Listener;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\AuditLog;
use App\Ticket\Domain\Entity\Comment;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Enum\TicketStatus;
use App\Ticket\Infrastructure\Doctrine\Listener\TicketAuditListener;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;

final class TicketAuditListenerTest extends TestCase
{
    private TicketAuditListener $listener;

    protected function setUp(): void
    {
        $this->listener = new TicketAuditListener();
    }

    public function testPostPersistCreatesTicketCreatedEntry(): void
    {
        $reporter = new User('r@test.com', 'hash', 'Reporter', UserRole::REPORTER);
        $ticket = new Ticket('T', 'D', TicketPriority::MEDIUM, new Category('IT'), $reporter, null, null, null);

        $persisted = [];
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(static function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        $this->listener->postPersist($ticket, new PostPersistEventArgs($ticket, $em));
        $this->listener->postFlush(new PostFlushEventArgs($em));

        $auditLogs = array_filter($persisted, static fn ($e) => $e instanceof AuditLog);
        $this->assertNotEmpty($auditLogs);
        $actions = array_map(static fn (AuditLog $l) => $l->getAction(), array_values($auditLogs));
        $this->assertContains('ticket.created', $actions);
    }

    public function testPostPersistOnCommentCreatesCommentAddedEntry(): void
    {
        $reporter = new User('r@test.com', 'hash', 'Reporter', UserRole::REPORTER);
        $ticket = new Ticket('T', 'D', TicketPriority::MEDIUM, new Category('IT'), $reporter, null, null, null);
        $comment = new Comment($ticket, $reporter, 'Hello', false);

        $persisted = [];
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(static function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        $this->listener->postPersistComment($comment, new PostPersistEventArgs($comment, $em));
        $this->listener->postFlush(new PostFlushEventArgs($em));

        $auditLogs = array_values(array_filter($persisted, static fn ($e) => $e instanceof AuditLog));
        $this->assertNotEmpty($auditLogs);
        $log = $auditLogs[0];
        $this->assertSame('comment.added', $log->getAction());
        $this->assertSame((string) $comment->getId(), $log->getPayload()['commentId']);
        $this->assertSame((string) $ticket->getId(), $log->getPayload()['ticketId']);
    }

    public function testPostPersistOnInternalCommentSkipsAuditEntry(): void
    {
        $reporter = new User('r@test.com', 'hash', 'Reporter', UserRole::REPORTER);
        $ticket = new Ticket('T', 'D', TicketPriority::MEDIUM, new Category('IT'), $reporter, null, null, null);
        $comment = new Comment($ticket, $reporter, 'Internal note', true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        $this->listener->postPersistComment($comment, new PostPersistEventArgs($comment, $em));
        $this->listener->postFlush(new PostFlushEventArgs($em));
    }

    public function testStatusChangedIsRecordedOnUpdate(): void
    {
        $reporter = new User('r@test.com', 'hash', 'Reporter', UserRole::REPORTER);
        $ticket = new Ticket('T', 'D', TicketPriority::MEDIUM, new Category('IT'), $reporter, null, null, null);

        $changeSet = ['status' => [TicketStatus::OPEN, TicketStatus::IN_PROGRESS]];

        $uow = $this->createStub(UnitOfWork::class);
        $uow->method('getEntityChangeSet')->willReturn($changeSet);

        $persisted = [];
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uow);
        $em->method('persist')->willReturnCallback(static function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        $this->listener->postUpdate($ticket, new PostUpdateEventArgs($ticket, $em));
        $this->listener->postFlush(new PostFlushEventArgs($em));

        $auditLogs = array_values(array_filter($persisted, static fn ($e) => $e instanceof AuditLog));
        $actions = array_map(static fn (AuditLog $l) => $l->getAction(), $auditLogs);
        $this->assertContains('ticket.status_changed', $actions);
    }
}
