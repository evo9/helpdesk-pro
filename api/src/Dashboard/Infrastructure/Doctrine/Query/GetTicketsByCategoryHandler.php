<?php

declare(strict_types=1);

namespace App\Dashboard\Infrastructure\Doctrine\Query;

use App\Dashboard\Application\Query\TicketsByCategoryItem;
use Doctrine\DBAL\Connection;

final class GetTicketsByCategoryHandler
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /** @return TicketsByCategoryItem[] */
    public function __invoke(): array
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
