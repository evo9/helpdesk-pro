<?php

declare(strict_types=1);

namespace App\Ticket\Infrastructure\Doctrine\Repository;

use App\Ticket\Domain\Entity\Comment;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Repository\CommentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository implements CommentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function findById(\Symfony\Component\Uid\Uuid $id): ?Comment
    {
        return $this->find($id);
    }

    public function findByTicket(Ticket $ticket): array
    {
        return $this->findBy(['ticket' => $ticket], ['createdAt' => 'ASC']);
    }

    public function save(Comment $comment): void
    {
        $this->getEntityManager()->persist($comment);
        $this->getEntityManager()->flush();
    }
}
