<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Ticket\Application\Command\CreateTicket;
use App\Ticket\Application\Command\CreateTicketHandler;
use App\Ticket\Infrastructure\Api\Provider\TicketStateProvider;
use App\Ticket\Infrastructure\Api\Resource\TicketResource;
use App\Ticket\Infrastructure\Messenger\Message\TicketCreatedMessage;
use App\Ticket\Infrastructure\Security\Voter\TicketVoter;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/** @implements ProcessorInterface<TicketResource, TicketResource> */
final class CreateTicketProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CreateTicketHandler $handler,
        private readonly TicketStateProvider $provider,
        private readonly Security $security,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TicketResource
    {
        if (!$this->security->isGranted(TicketVoter::CREATE)) {
            throw new AccessDeniedException();
        }

        /** @var TicketResource $data */
        /** @var User $user */
        $user = $this->security->getUser();

        $categoryId = $this->extractId($data->category ?? '');

        $ticket = ($this->handler)(new CreateTicket(
            title: $data->title ?? '',
            description: $data->description ?? '',
            categoryId: $categoryId,
            priority: $data->priority ?? 'medium',
            reporterId: (string) $user->getId(),
        ));

        $this->bus->dispatch(new TicketCreatedMessage((string) $ticket->getId()));

        return $this->provider->toResource($ticket);
    }

    private function extractId(string $iri): string
    {
        return basename($iri);
    }
}
