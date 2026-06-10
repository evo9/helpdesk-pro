<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Ticket\Domain\Entity\Comment;
use App\Ticket\Domain\Repository\CommentRepositoryInterface;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Api\Resource\CommentResource;
use App\Ticket\Infrastructure\Security\Voter\CommentVoter;
use App\Ticket\Infrastructure\Security\Voter\TicketVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

/** @implements ProviderInterface<CommentResource> */
final class CommentStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly CommentRepositoryInterface $commentRepo,
        private readonly Security $security,
    ) {
    }

    /** @return array<CommentResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $ticketId = $uriVariables['ticketId'] ?? '';

        $ticket = $this->ticketRepo->findById(Uuid::fromString((string) $ticketId));
        if (null === $ticket) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        if (!$this->security->isGranted(TicketVoter::VIEW, $ticket)) {
            throw new AccessDeniedException();
        }

        $comments = $this->commentRepo->findByTicket($ticket);

        $visible = array_filter(
            $comments,
            fn (Comment $c) => $this->security->isGranted(CommentVoter::VIEW, $c),
        );

        return array_map(fn (Comment $c) => $this->toResource($c), array_values($visible));
    }

    public function toResource(Comment $comment): CommentResource
    {
        $resource = new CommentResource();
        $resource->id = (string) $comment->getId();
        $resource->body = $comment->getBody();
        $resource->isInternal = $comment->isInternal();
        $resource->author = '/api/users/'.$comment->getAuthor()->getId();
        $resource->authorName = $comment->getAuthor()->getFullName();
        $resource->createdAt = $comment->getCreatedAt();

        return $resource;
    }
}
