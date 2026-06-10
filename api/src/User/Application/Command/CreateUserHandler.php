<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\User\Domain\Entity\User;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
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
            throw new UserAlreadyExistsException(
                \sprintf('User with email %s already exists.', $command->email),
            );
        }

        $user = new User($command->email, '', $command->fullName, $command->role);
        $user->updatePassword($this->passwordHasher->hashPassword($user, $command->plainPassword));

        $this->userRepo->save($user);

        return $user;
    }
}
