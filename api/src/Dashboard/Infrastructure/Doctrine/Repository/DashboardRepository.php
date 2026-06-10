<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Doctrine\Repository;

use App\Dashboard\Application\Query\AgentWorkloadItem;
use App\Dashboard\Application\Query\DashboardSummary;
use App\Dashboard\Application\Query\TicketsByCategoryItem;
use App\Dashboard\Domain\Repository\DashboardRepositoryInterface;
use App\Ticket\Domain\Enum\TicketStatus;
use Doctrine\DBAL\Connection;

final class DashboardRepository implements DashboardRepositoryInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getSummary(): DashboardSummary
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

    /** @return AgentWorkloadItem[] */
    public function getAgentWorkload(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT
                u.id::text AS agent_id,
                u.full_name AS name,
                COALESCE(SUM(CASE WHEN t.status IN ('open','in_progress','pending') THEN 1 ELSE 0 END), 0) AS active_tickets,
                COALESCE(SUM(CASE WHEN t.resolved_at >= NOW() - INTERVAL '30 days' AND t.resolved_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS resolved_last_30d
            FROM users u
            LEFT JOIN tickets t ON t.assignee_id = u.id
            WHERE u.role = 'agent' AND u.is_active = true
            GROUP BY u.id, u.full_name
            ORDER BY u.full_name",
        );

        return array_map(
            static fn (array $row) => new AgentWorkloadItem(
                agentId: $row['agent_id'],
                name: $row['name'],
                activeTickets: (int) $row['active_tickets'],
                resolvedLast30d: (int) $row['resolved_last_30d'],
            ),
            $rows,
        );
    }

    /** @return TicketsByCategoryItem[] */
    public function getTicketsByCategory(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT
                c.id::text AS category_id,
                c.name,
                COUNT(t.id) AS ticket_count
            FROM categories c
            LEFT JOIN tickets t ON t.category_id = c.id AND t.created_at >= NOW() - INTERVAL '30 days'
            WHERE c.is_active = true
            GROUP BY c.id, c.name
            ORDER BY ticket_count DESC",
        );

        return array_map(
            static fn (array $row) => new TicketsByCategoryItem(
                categoryId: $row['category_id'],
                categoryName: $row['name'],
                count: (int) $row['ticket_count'],
            ),
            $rows,
        );
    }
}
