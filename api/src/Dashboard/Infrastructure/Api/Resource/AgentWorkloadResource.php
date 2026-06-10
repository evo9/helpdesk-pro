<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Dashboard\Infrastructure\Api\Provider\AgentWorkloadProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'AgentWorkload',
    operations: [
        new GetCollection(
            uriTemplate: '/dashboard/agents',
            provider: AgentWorkloadProvider::class,
            normalizationContext: ['groups' => ['dashboard:agents']],
        ),
    ],
)]
final class AgentWorkloadResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['dashboard:agents'])]
    public string $agentId = '';

    #[Groups(['dashboard:agents'])]
    public string $name = '';

    #[Groups(['dashboard:agents'])]
    public int $activeTickets = 0;

    #[Groups(['dashboard:agents'])]
    public int $resolvedLast30d = 0;
}
