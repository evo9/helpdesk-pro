<?php

declare(strict_types=1);

namespace App\Ticket\Domain\Entity;

use App\Ticket\Infrastructure\Doctrine\Repository\AuditLogRepository;
use App\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_logs')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Ticket::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Ticket $ticket;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $actor;

    #[ORM\Column(length: 100)]
    private string $action;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        Ticket $ticket,
        User $actor,
        string $action,
        array $payload,
    ) {
        $this->id = Uuid::v7();
        $this->ticket = $ticket;
        $this->actor = $actor;
        $this->action = $action;
        $this->payload = $payload;
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

    public function getActor(): User
    {
        return $this->actor;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    /** @return array<string, mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
