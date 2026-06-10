<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Api\Provider;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Sla\Domain\Service\SlaStatusCalculator;
use App\Ticket\Application\Query\GetTicketDetail;
use App\Ticket\Application\Query\GetTicketDetailHandler;
use App\Ticket\Application\Query\GetTicketList;
use App\Ticket\Application\Query\GetTicketListHandler;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Enum\TicketStatus;
use App\Ticket\Infrastructure\Api\Resource\TicketResource;
use App\Ticket\Infrastructure\Security\Voter\TicketVoter;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/** @implements ProviderInterface<TicketResource> */
final class TicketStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly GetTicketListHandler $listHandler,
        private readonly GetTicketDetailHandler $detailHandler,
        private readonly Security $security,
        private readonly SlaStatusCalculator $slaStatusCalculator,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array
    {
        if ($operation instanceof CollectionOperationInterface) {
            /** @var User $user */
            $user = $this->security->getUser();

            $tickets = ($this->listHandler)(new GetTicketList(
                requesterId: (string) $user->getId(),
                requesterRole: $user->getRole(),
            ));

            return array_map(fn (Ticket $t) => $this->toResource($t), $tickets);
        }

        $ticket = ($this->detailHandler)(new GetTicketDetail($uriVariables['id']));

        if (null === $ticket) {
            throw new NotFoundHttpException('Ticket not found.');
        }

        if (!$this->security->isGranted(TicketVoter::VIEW, $ticket)) {
            throw new AccessDeniedException();
        }

        return $this->toResource($ticket);
    }

    public function toResource(Ticket $ticket): TicketResource
    {
        $resource = new TicketResource();
        $resource->id = (string) $ticket->getId();
        $resource->title = $ticket->getTitle();
        $resource->description = $ticket->getDescription();
        $resource->status = $ticket->getStatus()->value;
        $resource->priority = $ticket->getPriority()->value;
        $resource->category = '/api/categories/'.$ticket->getCategory()->getId();
        $resource->categoryName = $ticket->getCategory()->getName();
        $resource->reporter = '/api/users/'.$ticket->getReporter()->getId();
        $resource->reporterName = $ticket->getReporter()->getFullName();
        $resource->assignee = null !== $ticket->getAssignee()
            ? '/api/users/'.$ticket->getAssignee()->getId()
            : null;
        $resource->assigneeName = $ticket->getAssignee()?->getFullName();
        $resource->responseSlaStatus = $this->computeResponseSlaStatus($ticket);
        $resource->resolutionSlaStatus = $this->computeResolutionSlaStatus($ticket);
        $resource->responseDueAt = $ticket->getResponseDueAt();
        $resource->resolutionDueAt = $ticket->getResolutionDueAt();
        $resource->respondedAt = $ticket->getRespondedAt();
        $resource->resolvedAt = $ticket->getResolvedAt();
        $resource->createdAt = $ticket->getCreatedAt();
        $resource->updatedAt = $ticket->getUpdatedAt();

        return $resource;
    }

    private function computeResponseSlaStatus(Ticket $ticket): ?string
    {
        if (null === $ticket->getResponseDueAt() || null !== $ticket->getRespondedAt()) {
            return null;
        }
        if (\in_array($ticket->getStatus(), [TicketStatus::RESOLVED, TicketStatus::CLOSED], true)) {
            return null;
        }

        return $this->slaStatusCalculator->computeStatus(
            $ticket->getCreatedAt(),
            $ticket->getResponseDueAt(),
            new \DateTimeImmutable(),
        );
    }

    private function computeResolutionSlaStatus(Ticket $ticket): ?string
    {
        if (null === $ticket->getResolutionDueAt()) {
            return null;
        }
        if (\in_array($ticket->getStatus(), [TicketStatus::RESOLVED, TicketStatus::CLOSED], true)) {
            return null;
        }

        return $this->slaStatusCalculator->computeStatus(
            $ticket->getCreatedAt(),
            $ticket->getResolutionDueAt(),
            new \DateTimeImmutable(),
        );
    }
}
