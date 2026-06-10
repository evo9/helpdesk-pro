<?php

declare(strict_types=1);

namespace App\Ticket\Application\Command;

use App\Sla\Domain\Repository\CategoryRepositoryInterface;
use App\Sla\Domain\Repository\SlaPolicyRepositoryInterface;
use App\Sla\Domain\Service\SlaCalculator;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Repository\TicketRepositoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class CreateTicketHandler
{
    public function __construct(
        private readonly TicketRepositoryInterface $ticketRepo,
        private readonly CategoryRepositoryInterface $categoryRepo,
        private readonly SlaPolicyRepositoryInterface $slaPolicyRepo,
        private readonly UserRepositoryInterface $userRepo,
        private readonly SlaCalculator $slaCalculator,
    ) {
    }

    public function __invoke(CreateTicket $command): Ticket
    {
        $reporter = $this->userRepo->findById(Uuid::fromString($command->reporterId))
            ?? throw new \RuntimeException('Reporter not found: '.$command->reporterId);

        $category = $this->categoryRepo->findById(Uuid::fromString($command->categoryId))
            ?? throw new \RuntimeException('Category not found: '.$command->categoryId);

        $priority = \App\Sla\Domain\Enum\TicketPriority::from($command->priority);

        $slaPolicy = $this->slaPolicyRepo->findByCategoryAndPriority($category, $priority);

        $deadlines = null;
        if (null !== $slaPolicy) {
            $deadlines = $this->slaCalculator->calculate($slaPolicy, new \DateTimeImmutable());
        }

        $ticket = new Ticket(
            title: $command->title,
            description: $command->description,
            priority: $priority,
            category: $category,
            reporter: $reporter,
            slaPolicy: $slaPolicy,
            responseDueAt: $deadlines?->responseDueAt,
            resolutionDueAt: $deadlines?->resolutionDueAt,
        );

        $this->ticketRepo->save($ticket);

        return $ticket;
    }
}
