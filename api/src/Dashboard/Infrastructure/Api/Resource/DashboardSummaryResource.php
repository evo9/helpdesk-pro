<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Dashboard\Infrastructure\Api\Provider\DashboardSummaryProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'DashboardSummary',
    operations: [
        new Get(
            uriTemplate: '/dashboard/summary',
            uriVariables: [],
            provider: DashboardSummaryProvider::class,
            normalizationContext: ['groups' => ['dashboard:summary']],
        ),
    ],
)]
final class DashboardSummaryResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['dashboard:summary'])]
    public string $id = 'summary';

    /** @var array<string, int> */
    #[Groups(['dashboard:summary'])]
    public array $statuses = [];

    #[Groups(['dashboard:summary'])]
    public int $slaBreachedToday = 0;
}
