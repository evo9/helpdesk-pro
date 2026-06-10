<?php

declare(strict_types=1);

namespace App\Sla\Infrastructure\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Sla\Infrastructure\Api\Processor\CreateSlaPolicyProcessor;
use App\Sla\Infrastructure\Api\Processor\DeleteSlaPolicyProcessor;
use App\Sla\Infrastructure\Api\Processor\UpdateSlaPolicyProcessor;
use App\Sla\Infrastructure\Api\Provider\SlaPolicyStateProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'SlaPolicy',
    operations: [
        new GetCollection(
            uriTemplate: '/sla-policies',
            provider: SlaPolicyStateProvider::class,
            normalizationContext: ['groups' => ['sla:read']],
        ),
        new Get(
            uriTemplate: '/sla-policies/{id}',
            provider: SlaPolicyStateProvider::class,
            normalizationContext: ['groups' => ['sla:read']],
        ),
        new Post(
            uriTemplate: '/sla-policies',
            read: false,
            processor: CreateSlaPolicyProcessor::class,
            denormalizationContext: ['groups' => ['sla:write']],
            normalizationContext: ['groups' => ['sla:read']],
        ),
        new Patch(
            uriTemplate: '/sla-policies/{id}',
            provider: SlaPolicyStateProvider::class,
            processor: UpdateSlaPolicyProcessor::class,
            denormalizationContext: ['groups' => ['sla:update']],
            normalizationContext: ['groups' => ['sla:read']],
            inputFormats: ['json' => ['application/merge-patch+json', 'application/json']],
        ),
        new Delete(
            uriTemplate: '/sla-policies/{id}',
            provider: SlaPolicyStateProvider::class,
            processor: DeleteSlaPolicyProcessor::class,
        ),
    ],
)]
final class SlaPolicyResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['sla:read'])]
    public ?string $id = null;

    /** IRI of the category */
    #[Groups(['sla:read', 'sla:write'])]
    public ?string $category = null;

    #[Groups(['sla:read'])]
    public ?string $categoryName = null;

    #[Groups(['sla:read', 'sla:write'])]
    public ?string $priority = null;

    #[Groups(['sla:read', 'sla:write', 'sla:update'])]
    public ?int $responseHours = null;

    #[Groups(['sla:read', 'sla:write', 'sla:update'])]
    public ?int $resolutionHours = null;
}
