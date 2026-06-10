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
use App\Sla\Infrastructure\Api\Processor\CreateCategoryProcessor;
use App\Sla\Infrastructure\Api\Processor\DeleteCategoryProcessor;
use App\Sla\Infrastructure\Api\Processor\UpdateCategoryProcessor;
use App\Sla\Infrastructure\Api\Provider\CategoryStateProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Category',
    operations: [
        new GetCollection(
            provider: CategoryStateProvider::class,
            normalizationContext: ['groups' => ['category:read']],
        ),
        new Get(
            provider: CategoryStateProvider::class,
            normalizationContext: ['groups' => ['category:read']],
        ),
        new Post(
            read: false,
            processor: CreateCategoryProcessor::class,
            denormalizationContext: ['groups' => ['category:write']],
            normalizationContext: ['groups' => ['category:read']],
        ),
        new Patch(
            provider: CategoryStateProvider::class,
            processor: UpdateCategoryProcessor::class,
            denormalizationContext: ['groups' => ['category:update']],
            normalizationContext: ['groups' => ['category:read']],
            inputFormats: ['json' => ['application/merge-patch+json', 'application/json']],
        ),
        new Delete(
            provider: CategoryStateProvider::class,
            processor: DeleteCategoryProcessor::class,
        ),
    ],
)]
final class CategoryResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['category:read'])]
    public ?string $id = null;

    #[Groups(['category:read', 'category:write', 'category:update'])]
    public ?string $name = null;

    #[Groups(['category:read', 'category:write', 'category:update'])]
    public ?string $description = null;

    #[Groups(['category:read', 'category:update'])]
    public ?bool $isActive = null;
}
