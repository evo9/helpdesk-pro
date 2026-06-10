<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\User\Domain\Enum\UserRole;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class ChangeUserRoleHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
    ) {
    }

    public function __invoke(ChangeUserRole $command): void
    {
        $user = $this->userRepo->findById(Uuid::fromString($command->userId))
            ?? throw new NotFoundHttpException('User not found.');

        $user->changeRole(UserRole::fromSecurityRole($command->role));
        $this->userRepo->save($user);
    }
}
