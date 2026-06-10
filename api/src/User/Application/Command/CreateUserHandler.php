<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(CreateUser $command): User
    {
        if (null !== $this->userRepo->findByEmail($command->email)) {
            throw new UnprocessableEntityHttpException('A user with this email already exists.');
        }

        $role = UserRole::from($command->role);
        $user = new User($command->email, '', $command->fullName, $role);
        $user->updatePassword($this->passwordHasher->hashPassword($user, $command->plainPassword));

        $this->userRepo->save($user);

        return $user;
    }
}
