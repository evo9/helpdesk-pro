<?php

declare(strict_types=1);

namespace App\Sla\Domain\Entity;

use App\Sla\Domain\Enum\TicketPriority;
use App\Sla\Infrastructure\Doctrine\Repository\SlaPolicyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SlaPolicyRepository::class)]
#[ORM\Table(name: 'sla_policies')]
#[ORM\UniqueConstraint(name: 'uq_sla_category_priority', columns: ['category_id', 'priority'])]
class SlaPolicy
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\Column(type: 'string', enumType: TicketPriority::class)]
    private TicketPriority $priority;

    #[ORM\Column]
    private int $responseHours;

    #[ORM\Column]
    private int $resolutionHours;

    public function __construct(
        Category $category,
        TicketPriority $priority,
        int $responseHours,
        int $resolutionHours,
    ) {
        $this->id = Uuid::v7();
        $this->category = $category;
        $this->priority = $priority;
        $this->responseHours = $responseHours;
        $this->resolutionHours = $resolutionHours;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getPriority(): TicketPriority
    {
        return $this->priority;
    }

    public function getResponseHours(): int
    {
        return $this->responseHours;
    }

    public function getResolutionHours(): int
    {
        return $this->resolutionHours;
    }

    public function update(int $responseHours, int $resolutionHours): void
    {
        $this->responseHours = $responseHours;
        $this->resolutionHours = $resolutionHours;
    }
}
