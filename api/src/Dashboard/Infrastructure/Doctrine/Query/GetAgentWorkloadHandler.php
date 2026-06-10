<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Doctrine\Query;

use App\Dashboard\Application\Query\AgentWorkloadItem;
use Doctrine\DBAL\Connection;

final class GetAgentWorkloadHandler
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /** @return AgentWorkloadItem[] */
    public function __invoke(): array
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
}
