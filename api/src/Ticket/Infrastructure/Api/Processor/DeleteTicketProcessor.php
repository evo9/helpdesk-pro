<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\Ticket\Infrastructure\Api\Resource\TicketResource;
use App\Ticket\Infrastructure\Security\Voter\TicketVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

/** @implements ProcessorInterface<TicketResource, null> */
final class DeleteTicketProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        /** @var TicketResource $data */
        $ticket = $this->ticketRepo->findById(Uuid::fromString($uriVariables['id']));
        \assert($ticket instanceof Ticket);

        if (!$this->security->isGranted(TicketVoter::DELETE, $ticket)) {
            throw new AccessDeniedException();
        }

        $this->ticketRepo->remove($ticket);

        return null;
    }
}
