<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Ticket\Infrastructure\Api\Processor\CreateTicketProcessor;
use App\Ticket\Infrastructure\Api\Processor\DeleteTicketProcessor;
use App\Ticket\Infrastructure\Api\Processor\UpdateTicketProcessor;
use App\Ticket\Infrastructure\Api\Provider\TicketStateProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Ticket',
    operations: [
        new GetCollection(
            provider: TicketStateProvider::class,
            normalizationContext: ['groups' => ['ticket:read']],
        ),
        new Get(
            provider: TicketStateProvider::class,
            normalizationContext: ['groups' => ['ticket:read']],
        ),
        new Post(
            processor: CreateTicketProcessor::class,
            denormalizationContext: ['groups' => ['ticket:write']],
            normalizationContext: ['groups' => ['ticket:read']],
        ),
        new Patch(
            provider: TicketStateProvider::class,
            processor: UpdateTicketProcessor::class,
            denormalizationContext: ['groups' => ['ticket:update']],
            normalizationContext: ['groups' => ['ticket:read']],
            inputFormats: ['json' => ['application/merge-patch+json', 'application/json']],
        ),
        new Delete(
            provider: TicketStateProvider::class,
            processor: DeleteTicketProcessor::class,
        ),
    ],
)]
final class TicketResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['ticket:read'])]
    public ?string $id = null;

    /**
     * Optimistic lock version — must be sent back unchanged on PATCH requests
     * that modify status. A mismatch means another request already changed the ticket.
     */
    #[Groups(['ticket:read', 'ticket:update'])]
    public ?int $version = null;

    #[Groups(['ticket:read', 'ticket:write'])]
    public ?string $title = null;

    #[Groups(['ticket:read', 'ticket:write'])]
    public ?string $description = null;

    #[Groups(['ticket:read', 'ticket:update'])]
    public ?string $status = null;

    #[Groups(['ticket:read', 'ticket:write', 'ticket:update'])]
    public ?string $priority = null;

    /** IRI of the category, e.g. /api/categories/{uuid} */
    #[Groups(['ticket:read', 'ticket:write'])]
    public ?string $category = null;

    #[Groups(['ticket:read'])]
    public ?string $categoryName = null;

    #[Groups(['ticket:read'])]
    public ?string $reporter = null;

    #[Groups(['ticket:read'])]
    public ?string $reporterName = null;

    /** IRI of the assignee or null */
    #[Groups(['ticket:read', 'ticket:update'])]
    public ?string $assignee = null;

    #[Groups(['ticket:read'])]
    public ?string $assigneeName = null;

    /** 'ok', 'warning', 'breached', or null */
    #[Groups(['ticket:read'])]
    public ?string $responseSlaStatus = null;

    /** 'ok', 'warning', 'breached', or null */
    #[Groups(['ticket:read'])]
    public ?string $resolutionSlaStatus = null;

    #[Groups(['ticket:read'])]
    public ?\DateTimeImmutable $responseDueAt = null;

    #[Groups(['ticket:read'])]
    public ?\DateTimeImmutable $resolutionDueAt = null;

    #[Groups(['ticket:read'])]
    public ?\DateTimeImmutable $respondedAt = null;

    #[Groups(['ticket:read'])]
    public ?\DateTimeImmutable $resolvedAt = null;

    #[Groups(['ticket:read'])]
    public ?\DateTimeImmutable $createdAt = null;

    #[Groups(['ticket:read'])]
    public ?\DateTimeImmutable $updatedAt = null;
}
