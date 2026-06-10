<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Ticket\Application\Command\AddComment;
use App\Ticket\Application\Command\AddCommentHandler;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Api\Provider\CommentStateProvider;
use App\Ticket\Infrastructure\Api\Resource\CommentResource;
use App\Ticket\Infrastructure\Messenger\Message\CommentAddedMessage;
use App\Ticket\Infrastructure\Security\Voter\CommentVoter;
use App\Ticket\Infrastructure\Security\Voter\TicketVoter;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

/** @implements ProcessorInterface<CommentResource, CommentResource> */
final class AddCommentProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly AddCommentHandler $handler,
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly CommentStateProvider $provider,
        private readonly Security $security,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CommentResource
    {
        $ticketId = $uriVariables['ticketId'] ?? '';

        $ticket = $this->ticketRepo->findById(Uuid::fromString((string) $ticketId));
        if (null === $ticket) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        if (!$this->security->isGranted(TicketVoter::VIEW, $ticket)) {
            throw new AccessDeniedException();
        }

        if (!$this->security->isGranted(CommentVoter::CREATE, $ticket)) {
            throw new AccessDeniedException();
        }

        /** @var User $user */
        $user = $this->security->getUser();

        /** @var CommentResource $data */
        $isInternal = UserRole::REPORTER === $user->getRole()
            ? false
            : ($data->isInternal ?? false);

        $comment = ($this->handler)(new AddComment(
            ticketId: (string) $ticket->getId(),
            authorId: (string) $user->getId(),
            body: $data->body ?? '',
            isInternal: $isInternal,
        ));

        $this->bus->dispatch(new CommentAddedMessage((string) $comment->getId()));

        return $this->provider->toResource($comment);
    }
}
