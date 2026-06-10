<?php

declare(strict_types=1);

namespace App\Tests\Unit\Sla\Domain\Service;

use App\Sla\Domain\Service\SlaStatusCalculator;
use PHPUnit\Framework\TestCase;

final class SlaStatusCalculatorTest extends TestCase
{
    private SlaStatusCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new SlaStatusCalculator();
    }

    public function testOkWhenMoreThan20PercentRemainingTime(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-15 09:00:00');
        $dueAt = $createdAt->modify('+100 hours');
        $now = $createdAt->modify('+70 hours'); // 30% remaining

        $this->assertSame('ok', $this->calculator->computeStatus($createdAt, $dueAt, $now));
    }

    public function testOkJustAbove20PercentThreshold(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-15 09:00:00');
        $dueAt = $createdAt->modify('+100 hours');
        $now = $createdAt->modify('+79 hours 59 minutes 59 seconds'); // just above 20%

        $this->assertSame('ok', $this->calculator->computeStatus($createdAt, $dueAt, $now));
    }

    public function testWarningWhenExactly20PercentRemains(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-15 09:00:00');
        $dueAt = $createdAt->modify('+100 hours');
        $now = $createdAt->modify('+80 hours'); // exactly 20% remaining

        $this->assertSame('warning', $this->calculator->computeStatus($createdAt, $dueAt, $now));
    }

    public function testWarningWhenLessThan20PercentRemains(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-15 09:00:00');
        $dueAt = $createdAt->modify('+100 hours');
        $now = $createdAt->modify('+90 hours'); // 10% remaining

        $this->assertSame('warning', $this->calculator->computeStatus($createdAt, $dueAt, $now));
    }

    public function testBreachedWhenDeadlinePassed(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-15 09:00:00');
        $dueAt = $createdAt->modify('+24 hours');
        $now = $dueAt->modify('+1 second');

        $this->assertSame('breached', $this->calculator->computeStatus($createdAt, $dueAt, $now));
    }

    public function testBreachedWhenAtDeadlineExactMoment(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-15 09:00:00');
        $dueAt = $createdAt->modify('+24 hours');

        $this->assertSame('breached', $this->calculator->computeStatus($createdAt, $dueAt, $dueAt));
    }

    public function testWarningWithRealWorldValues(): void
    {
        // Response SLA of 4 hours, 45 min left = 18.75% remaining
        $createdAt = new \DateTimeImmutable('2024-01-15 09:00:00');
        $dueAt = $createdAt->modify('+4 hours');
        $now = $createdAt->modify('+3 hours 15 minutes'); // 45 min left of 240 = 18.75%

        $this->assertSame('warning', $this->calculator->computeStatus($createdAt, $dueAt, $now));
    }
}
