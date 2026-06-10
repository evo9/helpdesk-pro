<?php

declare(strict_types=1);

namespace App\Sla\Domain\Service;

final class SlaStatusCalculator
{
    public function computeStatus(
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $dueAt,
        \DateTimeImmutable $now,
    ): string {
        if ($now >= $dueAt) {
            return 'breached';
        }

        $totalSeconds = $dueAt->getTimestamp() - $createdAt->getTimestamp();
        $remainingSeconds = $dueAt->getTimestamp() - $now->getTimestamp();

        if ($totalSeconds <= 0 || ($remainingSeconds / $totalSeconds) <= 0.20) {
            return 'warning';
        }

        return 'ok';
    }
}
