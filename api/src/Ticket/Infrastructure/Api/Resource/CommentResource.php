<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use App\Ticket\Infrastructure\Api\Processor\AddCommentProcessor;
use App\Ticket\Infrastructure\Api\Provider\CommentStateProvider;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    shortName: 'Comment',
    operations: [
        new GetCollection(
            uriTemplate: '/tickets/{ticketId}/comments',
            uriVariables: [
                'ticketId' => new Link(fromClass: TicketResource::class, identifiers: ['id']),
            ],
            provider: CommentStateProvider::class,
            normalizationContext: ['groups' => ['comment:read']],
        ),
        new Post(
            uriTemplate: '/tickets/{ticketId}/comments',
            uriVariables: [
                'ticketId' => new Link(fromClass: TicketResource::class, identifiers: ['id']),
            ],
            read: false,
            processor: AddCommentProcessor::class,
            denormalizationContext: ['groups' => ['comment:write']],
            normalizationContext: ['groups' => ['comment:read']],
        ),
    ],
)]
final class CommentResource
{
    #[ApiProperty(identifier: true)]
    #[Groups(['comment:read'])]
    public ?string $id = null;

    #[Groups(['comment:read', 'comment:write'])]
    public ?string $body = null;

    #[Groups(['comment:read', 'comment:write'])]
    public ?bool $isInternal = null;

    /** IRI of the author */
    #[Groups(['comment:read'])]
    public ?string $author = null;

    #[Groups(['comment:read'])]
    public ?string $authorName = null;

    #[Groups(['comment:read'])]
    public ?\DateTimeImmutable $createdAt = null;
}
