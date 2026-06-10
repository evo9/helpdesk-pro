<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket\Domain\Entity;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Entity\SlaPolicy;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Comment;
use App\Ticket\Domain\Entity\Ticket;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;

final class CommentTest extends TestCase
{
    public function testCreatesPublicComment(): void
    {
        $ticket = $this->createTicket();
        $author = new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT);

        $comment = new Comment(
            ticket: $ticket,
            author: $author,
            body: 'We are looking into this issue.',
            isInternal: false,
        );

        $this->assertSame($ticket, $comment->getTicket());
        $this->assertSame($author, $comment->getAuthor());
        $this->assertSame('We are looking into this issue.', $comment->getBody());
        $this->assertFalse($comment->isInternal());
        $this->assertNotNull($comment->getId());
        $this->assertNotNull($comment->getCreatedAt());
    }

    public function testCreatesInternalNote(): void
    {
        $ticket = $this->createTicket();
        $agent = new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT);

        $comment = new Comment(
            ticket: $ticket,
            author: $agent,
            body: 'Internal note: escalate to hardware team.',
            isInternal: true,
        );

        $this->assertTrue($comment->isInternal());
    }

    private function createTicket(): Ticket
    {
        $reporter = new User('r@example.com', 'hash', 'Reporter', UserRole::REPORTER);
        $category = new Category('Hardware');
        $policy = new SlaPolicy($category, TicketPriority::MEDIUM, 4, 24);

        return new Ticket('Title', 'Desc', TicketPriority::MEDIUM, $category, $reporter, $policy,
            new \DateTimeImmutable('+4 hours'), new \DateTimeImmutable('+24 hours'));
    }
}
