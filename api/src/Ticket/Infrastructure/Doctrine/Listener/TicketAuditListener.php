<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Doctrine\Listener;

use App\Ticket\Domain\Entity\AuditLog;
use App\Ticket\Domain\Entity\Comment;
use App\Ticket\Domain\Entity\Ticket;
use App\User\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, entity: Ticket::class)]
#[AsEntityListener(event: Events::postUpdate, entity: Ticket::class)]
#[AsEntityListener(event: Events::postPersist, entity: Comment::class, method: 'postPersistComment')]
#[AsDoctrineListener(event: Events::postFlush)]
class TicketAuditListener
{
    /** @var AuditLog[] */
    private array $pending = [];

    public function postPersist(Ticket $ticket, PostPersistEventArgs $event): void
    {
        $this->pending[] = new AuditLog($ticket, $ticket->getReporter(), 'ticket.created', []);
    }

    public function postUpdate(Ticket $ticket, PostUpdateEventArgs $event): void
    {
        $em = $event->getObjectManager();
        $changeSet = $em->getUnitOfWork()->getEntityChangeSet($ticket);

        $actor = $ticket->getAssignee() ?? $ticket->getReporter();

        foreach ($this->extractAuditEntries($changeSet) as [$action, $payload]) {
            $this->pending[] = new AuditLog($ticket, $actor, $action, $payload);
        }
    }

    public function postPersistComment(Comment $comment, PostPersistEventArgs $event): void
    {
        if ($comment->isInternal()) {
            return;
        }

        $ticket = $comment->getTicket();
        $this->pending[] = new AuditLog($ticket, $comment->getAuthor(), 'comment.added', [
            'commentId' => (string) $comment->getId(),
            'ticketId' => (string) $ticket->getId(),
        ]);
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if ([] === $this->pending) {
            return;
        }

        $em = $event->getObjectManager();
        $logs = $this->pending;
        $this->pending = [];

        foreach ($logs as $log) {
            $em->persist($log);
        }

        $em->flush();
    }

    /**
     * @param array<string, mixed> $changeSet
     *
     * @return array<array{string, array<string, mixed>}>
     */
    private function extractAuditEntries(array $changeSet): array
    {
        $entries = [];

        if (isset($changeSet['status'])) {
            [$old, $new] = $changeSet['status'];
            $entries[] = ['ticket.status_changed', [
                'from' => $old instanceof \BackedEnum ? $old->value : $old,
                'to' => $new instanceof \BackedEnum ? $new->value : $new,
            ]];
        }

        if (isset($changeSet['assignee'])) {
            [$old, $new] = $changeSet['assignee'];
            $entries[] = ['ticket.assigned', [
                'from' => $old instanceof User ? (string) $old->getId() : null,
                'to' => $new instanceof User ? (string) $new->getId() : null,
            ]];
        }

        if (isset($changeSet['priority'])) {
            [$old, $new] = $changeSet['priority'];
            $entries[] = ['ticket.priority_changed', [
                'from' => $old instanceof \BackedEnum ? $old->value : $old,
                'to' => $new instanceof \BackedEnum ? $new->value : $new,
            ]];
        }

        return $entries;
    }
}
