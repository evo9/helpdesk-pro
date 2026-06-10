<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Doctrine\Query;

use App\Dashboard\Application\Query\DashboardSummary;
use App\Ticket\Domain\Enum\TicketStatus;
use Doctrine\DBAL\Connection;

final class GetDashboardSummaryHandler
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(): DashboardSummary
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT status, COUNT(*) AS cnt FROM tickets GROUP BY status',
        );

        $statusCounts = array_fill_keys(
            array_map(static fn (TicketStatus $s) => $s->value, TicketStatus::cases()),
            0,
        );

        foreach ($rows as $row) {
            $statusCounts[$row['status']] = (int) $row['cnt'];
        }

        $slaBreachedToday = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM audit_logs WHERE action = 'ticket.sla_breached' AND created_at::date = CURRENT_DATE",
        );

        return new DashboardSummary($statusCounts, $slaBreachedToday);
    }
}
