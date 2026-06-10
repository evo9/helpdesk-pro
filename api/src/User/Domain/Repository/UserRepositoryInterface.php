<?php

declare(strict_types=1);

namespace App\User\Domain\Repository;

use App\User\Domain\Entity\User;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function findById(Uuid $id): ?User;

    public function findByEmail(string $email): ?User;

    /** @return User[] */
    public function findAll(): array;

    /** @return User[] */
    public function findActiveAgents(): array;

    /** @return User[] */
    public function findManagers(): array;

    public function save(User $user): void;

    public function remove(User $user): void;
}
