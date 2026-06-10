<?php

declare(strict_types=1);

namespace App\Ticket\Domain\Entity;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Entity\SlaPolicy;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Enum\TicketStatus;
use App\Ticket\Infrastructure\Doctrine\Repository\TicketRepository;
use App\User\Domain\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\Table(name: 'tickets')]
#[ORM\HasLifecycleCallbacks]
class Ticket
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'string', enumType: TicketStatus::class)]
    private TicketStatus $status;

    #[ORM\Column(type: 'string', enumType: TicketPriority::class)]
    private TicketPriority $priority;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $reporter;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $assignee = null;

    #[ORM\ManyToOne(targetEntity: SlaPolicy::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?SlaPolicy $slaPolicy;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $responseDueAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resolutionDueAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $respondedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        string $title,
        string $description,
        TicketPriority $priority,
        Category $category,
        User $reporter,
        ?SlaPolicy $slaPolicy,
        ?\DateTimeImmutable $responseDueAt,
        ?\DateTimeImmutable $resolutionDueAt,
    ) {
        $this->id = Uuid::v7();
        $this->title = $title;
        $this->description = $description;
        $this->status = TicketStatus::OPEN;
        $this->priority = $priority;
        $this->category = $category;
        $this->reporter = $reporter;
        $this->slaPolicy = $slaPolicy;
        $this->responseDueAt = $responseDueAt;
        $this->resolutionDueAt = $resolutionDueAt;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStatus(): TicketStatus
    {
        return $this->status;
    }

    public function getPriority(): TicketPriority
    {
        return $this->priority;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getReporter(): User
    {
        return $this->reporter;
    }

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function getSlaPolicy(): ?SlaPolicy
    {
        return $this->slaPolicy;
    }

    public function getResponseDueAt(): ?\DateTimeImmutable
    {
        return $this->responseDueAt;
    }

    public function getResolutionDueAt(): ?\DateTimeImmutable
    {
        return $this->resolutionDueAt;
    }

    public function getRespondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function assignTo(?User $agent): void
    {
        $this->assignee = $agent;
        $this->touch();
    }

    public function changeStatus(TicketStatus $status): void
    {
        $this->status = $status;
        $this->touch();
    }

    public function changePriority(TicketPriority $priority): void
    {
        $this->priority = $priority;
        $this->touch();
    }

    public function markResponded(): void
    {
        if (null === $this->respondedAt) {
            $this->respondedAt = new \DateTimeImmutable();
        }
        $this->touch();
    }

    public function markResolved(): void
    {
        $this->resolvedAt = new \DateTimeImmutable();
        $this->touch();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
