<?php

declare(strict_types=1);

namespace App\Ticket\Domain\Entity;

use App\Ticket\Infrastructure\Doctrine\Repository\CommentRepository;
use App\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'comments')]
#[ORM\Index(name: 'idx_comments_ticket_internal', columns: ['ticket_id', 'is_internal'])]
class Comment
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Ticket $ticket;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $author;

    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\Column]
    private bool $isInternal;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Ticket $ticket,
        User $author,
        string $body,
        bool $isInternal,
    ) {
        $this->id = Uuid::v7();
        $this->ticket = $ticket;
        $this->author = $author;
        $this->body = $body;
        $this->isInternal = $isInternal;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTicket(): Ticket
    {
        return $this->ticket;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function isInternal(): bool
    {
        return $this->isInternal;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
