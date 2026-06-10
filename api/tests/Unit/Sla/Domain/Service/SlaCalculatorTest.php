<?php

declare(strict_types=1);

namespace App\Tests\Unit\Sla\Domain\Service;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Entity\SlaPolicy;
use App\Sla\Domain\Enum\TicketPriority;
use App\Sla\Domain\Service\SlaCalculator;
use PHPUnit\Framework\TestCase;

final class SlaCalculatorTest extends TestCase
{
    private SlaCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new SlaCalculator();
    }

    public function testCalculatesResponseDueAt(): void
    {
        $policy = new SlaPolicy(new Category('IT'), TicketPriority::HIGH, responseHours: 4, resolutionHours: 24);
        $createdAt = new \DateTimeImmutable('2024-01-15 09:00:00');

        $deadlines = $this->calculator->calculate($policy, $createdAt);

        $this->assertEquals(
            new \DateTimeImmutable('2024-01-15 13:00:00'),
            $deadlines->responseDueAt,
        );
    }

    public function testCalculatesResolutionDueAt(): void
    {
        $policy = new SlaPolicy(new Category('IT'), TicketPriority::HIGH, responseHours: 4, resolutionHours: 24);
        $createdAt = new \DateTimeImmutable('2024-01-15 09:00:00');

        $deadlines = $this->calculator->calculate($policy, $createdAt);

        $this->assertEquals(
            new \DateTimeImmutable('2024-01-16 09:00:00'),
            $deadlines->resolutionDueAt,
        );
    }

    public function testCriticalPriorityHasTighterDeadlines(): void
    {
        $criticalPolicy = new SlaPolicy(new Category('IT'), TicketPriority::CRITICAL, responseHours: 1, resolutionHours: 4);
        $lowPolicy = new SlaPolicy(new Category('IT'), TicketPriority::LOW, responseHours: 48, resolutionHours: 120);
        $createdAt = new \DateTimeImmutable('2024-01-15 09:00:00');

        $criticalDeadlines = $this->calculator->calculate($criticalPolicy, $createdAt);
        $lowDeadlines = $this->calculator->calculate($lowPolicy, $createdAt);

        $this->assertLessThan($lowDeadlines->responseDueAt, $criticalDeadlines->responseDueAt);
        $this->assertLessThan($lowDeadlines->resolutionDueAt, $criticalDeadlines->resolutionDueAt);
    }

    public function testDeadlinesCrossOverMidnight(): void
    {
        $policy = new SlaPolicy(new Category('IT'), TicketPriority::MEDIUM, responseHours: 8, resolutionHours: 48);
        $createdAt = new \DateTimeImmutable('2024-01-15 22:00:00');

        $deadlines = $this->calculator->calculate($policy, $createdAt);

        $this->assertEquals(new \DateTimeImmutable('2024-01-16 06:00:00'), $deadlines->responseDueAt);
        $this->assertEquals(new \DateTimeImmutable('2024-01-17 22:00:00'), $deadlines->resolutionDueAt);
    }
}
