<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Dashboard\Infrastructure\Api\Provider\TicketsByCategoryProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'TicketsByCategory',
    operations: [
        new GetCollection(
            uriTemplate: '/dashboard/tickets-by-category',
            provider: TicketsByCategoryProvider::class,
            normalizationContext: ['groups' => ['dashboard:tickets-by-category']],
        ),
    ],
)]
final class TicketsByCategoryResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['dashboard:tickets-by-category'])]
    public string $categoryId = '';

    #[Groups(['dashboard:tickets-by-category'])]
    public string $categoryName = '';

    #[Groups(['dashboard:tickets-by-category'])]
    public int $count = 0;
}
