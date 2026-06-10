<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Console;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use App\User\Infrastructure\Doctrine\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:user:create', description: 'Create a user with a specific role')]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email address')
            ->addArgument('password', InputArgument::REQUIRED, 'User password')
            ->addOption('role', null, InputOption::VALUE_REQUIRED, 'User role (reporter, agent, manager)', 'reporter')
            ->addOption('full-name', null, InputOption::VALUE_REQUIRED, 'User full name', 'New User');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $email */
        $email = $input->getArgument('email');
        /** @var string $password */
        $password = $input->getArgument('password');
        /** @var string $roleName */
        $roleName = $input->getOption('role');
        /** @var string $fullName */
        $fullName = $input->getOption('full-name');

        if ($this->userRepository->findByEmail($email) !== null) {
            $io->error(sprintf('User with email %s already exists.', $email));

            return Command::FAILURE;
        }

        $role = UserRole::tryFrom($roleName);
        if ($role === null) {
            $io->error(sprintf('Invalid role "%s". Valid values: reporter, agent, manager.', $roleName));

            return Command::FAILURE;
        }

        $user = new User($email, '', $fullName, $role);
        $hash = $this->passwordHasher->hashPassword($user, $password);
        $user->updatePassword($hash);

        $this->userRepository->save($user);

        $io->success(sprintf('User created: %s (%s)', $email, $role->value));

        return Command::SUCCESS;
    }
}
