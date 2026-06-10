<?php

declare(strict_types=1);

namespace App\User\Application\Command\RegisterUser;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
final class RegisterUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(RegisterUserCommand $command): User
    {
        if (null !== $this->userRepository->findByEmail($command->email)) {
            throw new UserAlreadyExistsException(\sprintf('User with email %s already exists.', $command->email));
        }

        $user = new User($command->email, '', $command->fullName, UserRole::REPORTER);
        $user->updatePassword($this->passwordHasher->hashPassword($user, $command->password));

        $this->userRepository->save($user);

        return $user;
    }
}
