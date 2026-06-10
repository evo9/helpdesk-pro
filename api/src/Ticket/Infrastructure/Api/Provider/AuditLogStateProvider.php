<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Ticket\Domain\Entity\AuditLog;
use App\Ticket\Domain\Repository\AuditLogRepositoryInterface;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Api\Resource\AuditLogResource;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

/** @implements ProviderInterface<AuditLogResource> */
final class AuditLogStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly AuditLogRepositoryInterface $auditLogRepo,
        private readonly Security $security,
    ) {
    }

    /** @return array<AuditLogResource> */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (UserRole::REPORTER === $user->getRole()) {
            throw new AccessDeniedException();
        }

        $ticketId = $uriVariables['ticketId'] ?? '';
        $ticket = $this->ticketRepo->findById(Uuid::fromString((string) $ticketId));

        if (null === $ticket) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        return array_map(
            fn (AuditLog $log) => $this->toResource($log),
            $this->auditLogRepo->findByTicketSortedDesc($ticket),
        );
    }

    private function toResource(AuditLog $log): AuditLogResource
    {
        $resource = new AuditLogResource();
        $resource->id = (string) $log->getId();
        $resource->action = $log->getAction();
        $resource->payload = $log->getPayload();
        $resource->actorId = (string) $log->getActor()->getId();
        $resource->actorName = $log->getActor()->getFullName();
        $resource->createdAt = $log->getCreatedAt();

        return $resource;
    }
}
