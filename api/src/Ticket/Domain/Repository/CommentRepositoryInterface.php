<?php

declare(strict_types=1);

namespace App\Ticket\Domain\Repository;

use App\Ticket\Domain\Entity\Comment;
use App\Ticket\Domain\Entity\Ticket;
use Symfony\Component\Uid\Uuid;

interface CommentRepositoryInterface
{
    public function findById(Uuid $id): ?Comment;

    /** @return Comment[] */
    public function findByTicket(Ticket $ticket): array;

    public function save(Comment $comment): void;
}
