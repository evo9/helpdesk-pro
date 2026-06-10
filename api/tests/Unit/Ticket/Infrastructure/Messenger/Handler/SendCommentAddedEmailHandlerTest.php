<?php

declare(strict_types=1);

namespace App\Tests\Unit\Ticket\Infrastructure\Messenger\Handler;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Comment;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Repository\CommentRepositoryInterface;
use App\Ticket\Infrastructure\Messenger\Handler\SendCommentAddedEmailHandler;
use App\Ticket\Infrastructure\Messenger\Message\CommentAddedMessage;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class SendCommentAddedEmailHandlerTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $mailer;
    private \PHPUnit\Framework\MockObject\Stub $commentRepo;
    private SendCommentAddedEmailHandler $handler;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->commentRepo = $this->createStub(CommentRepositoryInterface::class);
        $this->handler = new SendCommentAddedEmailHandler($this->commentRepo, $this->mailer);
    }

    public function testSkipsInternalComment(): void
    {
        $ticket = $this->makeTicket();
        $agent = new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT);
        $comment = new Comment($ticket, $agent, 'Internal note', true);

        $this->commentRepo->method('findById')->willReturn($comment);
        $this->mailer->expects($this->never())->method('send');

        ($this->handler)(new CommentAddedMessage($comment->getId()->toString()));
    }

    public function testSendsEmailToReporterWhenAgentComments(): void
    {
        $reporter = new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);
        $agent = new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT);
        $comment = new Comment($ticket, $agent, 'Working on it', false);

        $this->commentRepo->method('findById')->willReturn($comment);

        $sentTo = null;
        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(static function (Email $email) use (&$sentTo): void {
                $sentTo = $email->getTo()[0]->getAddress();
            });

        ($this->handler)(new CommentAddedMessage($comment->getId()->toString()));

        $this->assertSame('reporter@example.com', $sentTo);
    }

    public function testSendsEmailToAssigneeWhenReporterComments(): void
    {
        $reporter = new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);
        $agent = new User('agent@example.com', 'hash', 'Agent', UserRole::AGENT);
        $ticket->assignTo($agent);
        $comment = new Comment($ticket, $reporter, 'Any update?', false);

        $this->commentRepo->method('findById')->willReturn($comment);

        $sentTo = null;
        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(static function (Email $email) use (&$sentTo): void {
                $sentTo = $email->getTo()[0]->getAddress();
            });

        ($this->handler)(new CommentAddedMessage($comment->getId()->toString()));

        $this->assertSame('agent@example.com', $sentTo);
    }

    public function testSkipsWhenReporterCommentsAndNoAssignee(): void
    {
        $reporter = new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);
        $ticket = $this->makeTicket($reporter);
        $comment = new Comment($ticket, $reporter, 'Any update?', false);

        $this->commentRepo->method('findById')->willReturn($comment);
        $this->mailer->expects($this->never())->method('send');

        ($this->handler)(new CommentAddedMessage($comment->getId()->toString()));
    }

    private function makeTicket(?User $reporter = null): Ticket
    {
        $reporter ??= new User('reporter@example.com', 'hash', 'Reporter', UserRole::REPORTER);

        return new Ticket('Test ticket', 'Description', TicketPriority::MEDIUM, new Category('IT'), $reporter, null, null, null);
    }
}
