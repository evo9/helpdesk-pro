<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Domain\Enum\UserRole;
use App\User\Infrastructure\Doctrine\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'idx_users_role', columns: ['role'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private Uuid $id;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column]
    private string $passwordHash;

    #[ORM\Column(length: 255)]
    private string $fullName;

    #[ORM\Column(type: 'string', enumType: UserRole::class)]
    private UserRole $role;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $email,
        string $passwordHash,
        string $fullName,
        UserRole $role,
    ) {
        $this->id = Uuid::v7();
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->fullName = $fullName;
        $this->role = $role;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function changeRole(UserRole $role): void
    {
        $this->role = $role;
    }

    public function updatePassword(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    // UserInterface

    /** @phpstan-return non-empty-string */
    public function getUserIdentifier(): string
    {
        \assert('' !== $this->email);

        return $this->email;
    }

    public function getRoles(): array
    {
        return [$this->role->toSecurityRole()];
    }

    public function eraseCredentials(): void
    {
    }

    // PasswordAuthenticatedUserInterface

    public function getPassword(): string
    {
        return $this->passwordHash;
    }
}
