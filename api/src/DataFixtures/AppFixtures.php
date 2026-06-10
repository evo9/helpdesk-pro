<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use App\User\Infrastructure\Doctrine\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = [
            ['manager@example.com', UserRole::MANAGER, 'Alice Manager'],
            ['agent1@example.com', UserRole::AGENT, 'Bob Agent'],
            ['agent2@example.com', UserRole::AGENT, 'Carol Agent'],
            ['reporter1@example.com', UserRole::REPORTER, 'Dave Reporter'],
            ['reporter2@example.com', UserRole::REPORTER, 'Eve Reporter'],
        ];

        foreach ($users as [$email, $role, $fullName]) {
            $user = new User($email, '', $fullName, $role);
            $hash = $this->passwordHasher->hashPassword($user, 'password');
            $user->updatePassword($hash);
            $this->userRepository->save($user);
        }
    }
}
