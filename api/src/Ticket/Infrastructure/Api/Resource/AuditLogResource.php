<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Ticket\Infrastructure\Api\Provider\AuditLogStateProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AuditLog',
    operations: [
        new GetCollection(
            uriTemplate: '/tickets/{ticketId}/audit',
            uriVariables: [
                'ticketId' => new Link(fromClass: TicketResource::class, identifiers: ['id']),
            ],
            provider: AuditLogStateProvider::class,
            normalizationContext: ['groups' => ['audit:read']],
        ),
    ],
)]
final class AuditLogResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['audit:read'])]
    public ?string $id = null;

    #[Groups(['audit:read'])]
    public ?string $action = null;

    /** @var array<string, mixed> */
    #[Groups(['audit:read'])]
    public array $payload = [];

    #[Groups(['audit:read'])]
    public ?string $actorId = null;

    #[Groups(['audit:read'])]
    public ?string $actorName = null;

    #[Groups(['audit:read'])]
    public ?\DateTimeImmutable $createdAt = null;
}
