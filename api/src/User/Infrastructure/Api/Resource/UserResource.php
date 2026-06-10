<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\User\Infrastructure\Api\Processor\CreateUserProcessor;
use App\User\Infrastructure\Api\Processor\UpdateUserProcessor;
use App\User\Infrastructure\Api\Provider\UserStateProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'User',
    operations: [
        new GetCollection(
            provider: UserStateProvider::class,
            normalizationContext: ['groups' => ['user:read']],
        ),
        new Get(
            uriTemplate: '/users/me',
            uriVariables: [],
            provider: UserStateProvider::class,
            normalizationContext: ['groups' => ['user:read']],
        ),
        new Get(
            provider: UserStateProvider::class,
            normalizationContext: ['groups' => ['user:read']],
        ),
        new Post(
            read: false,
            processor: CreateUserProcessor::class,
            denormalizationContext: ['groups' => ['user:write']],
            normalizationContext: ['groups' => ['user:read']],
        ),
        new Patch(
            provider: UserStateProvider::class,
            processor: UpdateUserProcessor::class,
            denormalizationContext: ['groups' => ['user:update']],
            normalizationContext: ['groups' => ['user:read']],
            inputFormats: ['json' => ['application/merge-patch+json', 'application/json']],
        ),
    ],
)]
final class UserResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['user:read'])]
    public ?string $id = null;

    #[Groups(['user:read', 'user:write'])]
    public ?string $email = null;

    /** Write-only: plain-text password for creation */
    #[ApiProperty(readable: false)]
    #[Groups(['user:write'])]
    public ?string $password = null;

    #[Groups(['user:read', 'user:write'])]
    public ?string $fullName = null;

    #[Groups(['user:read', 'user:write', 'user:update'])]
    public ?string $role = null;

    #[Groups(['user:read', 'user:update'])]
    public ?bool $isActive = null;

    #[Groups(['user:read'])]
    public ?\DateTimeImmutable $createdAt = null;
}
