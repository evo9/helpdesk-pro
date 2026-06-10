<?php

declare(strict_types=1);

namespace App\Sla\Application\Command;

use App\Sla\Domain\Entity\SlaPolicy;
use App\Sla\Domain\Enum\TicketPriority;
use App\Sla\Domain\Repository\CategoryRepositoryInterface;
use App\Sla\Domain\Repository\SlaPolicyRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Uid\Uuid;

final class CreateSlaPolicyHandler
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepo,
        private readonly SlaPolicyRepositoryInterface $slaPolicyRepo,
    ) {
    }

    public function __invoke(CreateSlaPolicy $command): SlaPolicy
    {
        $category = $this->categoryRepo->findById(Uuid::fromString($command->categoryId))
            ?? throw new NotFoundHttpException('Category not found.');

        $priority = TicketPriority::from($command->priority);

        if (null !== $this->slaPolicyRepo->findByCategoryAndPriority($category, $priority)) {
            throw new UnprocessableEntityHttpException(\sprintf('An SLA policy for category "%s" and priority "%s" already exists.', $category->getName(), $priority->value));
        }

        $policy = new SlaPolicy($category, $priority, $command->responseHours, $command->resolutionHours);
        $this->slaPolicyRepo->save($policy);

        return $policy;
    }
}
