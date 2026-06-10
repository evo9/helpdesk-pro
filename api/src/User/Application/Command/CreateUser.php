<?php

declare(strict_types=1);

namespace App\User\Application\Command;

use App\User\Domain\Enum\UserRole;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateUser
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $plainPassword,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2)]
        public string $fullName,

        public UserRole $role = UserRole::REPORTER,
    ) {
    }
}
