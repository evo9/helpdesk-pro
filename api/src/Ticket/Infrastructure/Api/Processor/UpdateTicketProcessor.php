<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Ticket\Application\Command\AssignTicket;
use App\Ticket\Application\Command\AssignTicketHandler;
use App\Ticket\Application\Command\ChangeTicketStatus;
use App\Ticket\Application\Command\ChangeTicketStatusHandler;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Enum\TicketStatus;
use App\Ticket\Domain\Exception\InvalidStatusTransitionException;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Api\Provider\TicketStateProvider;
use Doctrine\ORM\OptimisticLockException;
use App\Ticket\Infrastructure\Api\Resource\TicketResource;
use App\Ticket\Infrastructure\Messenger\Message\TicketAssignedMessage;
use App\Ticket\Infrastructure\Messenger\Message\TicketStatusChangedMessage;
use App\Ticket\Infrastructure\Security\Voter\TicketVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

/** @implements ProcessorInterface<TicketResource, TicketResource> */
final class UpdateTicketProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ChangeTicketStatusHandler $changeStatusHandler,
        private readonly AssignTicketHandler $assignHandler,
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly TicketStateProvider $provider,
        private readonly Security $security,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TicketResource
    {
        /** @var TicketResource $data */
        /** @var TicketResource $previous */
        $previous = $context['previous_data'];
        $ticketId = $uriVariables['id'];

        $ticket = $this->ticketRepo->findById(Uuid::fromString($ticketId));
        \assert($ticket instanceof Ticket);

        if (null !== $data->status && $data->status !== $previous->status) {
            $this->authorizeStatusChange($ticket, $data->status);

            try {
                ($this->changeStatusHandler)(new ChangeTicketStatus(
                    $ticketId,
                    $data->status,
                    $data->version ?? $previous->version ?? 1,
                ));
            } catch (OptimisticLockException $e) {
                throw new ConflictHttpException('The ticket was modified by another request. Reload and try again.', $e);
            } catch (InvalidStatusTransitionException $e) {
                throw new UnprocessableEntityHttpException($e->getMessage(), $e);
            }

            $this->bus->dispatch(new TicketStatusChangedMessage($ticketId, $previous->status ?? '', $data->status));
        }

        if ($data->assignee !== $previous->assignee) {
            if (!$this->security->isGranted(TicketVoter::ASSIGN, $ticket)) {
                throw new AccessDeniedException();
            }

            $agentId = null !== $data->assignee ? $this->extractId($data->assignee) : null;
            ($this->assignHandler)(new AssignTicket($ticketId, $agentId));
            $this->bus->dispatch(new TicketAssignedMessage($ticketId));
        }

        if (null !== $data->priority && $data->priority !== $previous->priority) {
            if (!$this->security->isGranted(TicketVoter::ASSIGN, $ticket)) {
                throw new AccessDeniedException();
            }

            $ticket = $this->ticketRepo->findById(Uuid::fromString($ticketId));
            \assert($ticket instanceof Ticket);
            $ticket->changePriority(\App\Sla\Domain\Enum\TicketPriority::from($data->priority));
            $this->ticketRepo->save($ticket);
        }

        $ticket = $this->ticketRepo->findById(Uuid::fromString($ticketId));
        \assert($ticket instanceof Ticket);

        return $this->provider->toResource($ticket);
    }

    private function authorizeStatusChange(Ticket $ticket, string $newStatus): void
    {
        $attribute = (TicketStatus::OPEN->value === $newStatus && TicketStatus::RESOLVED === $ticket->getStatus())
            ? TicketVoter::REOPEN
            : TicketVoter::UPDATE_STATUS;

        if (!$this->security->isGranted($attribute, $ticket)) {
            throw new AccessDeniedException();
        }
    }

    private function extractId(string $iri): string
    {
        return basename($iri);
    }
}
