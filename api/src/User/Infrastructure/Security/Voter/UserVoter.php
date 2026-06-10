<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security\Voter;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, User|null>
 */
final class UserVoter extends Voter
{
    public const LIST = 'USER_LIST';
    public const CREATE = 'USER_CREATE';
    public const VIEW = 'USER_VIEW';
    public const UPDATE = 'USER_UPDATE';

    private const ATTRIBUTES = [self::LIST, self::CREATE, self::VIEW, self::UPDATE];

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!\in_array($attribute, self::ATTRIBUTES, true)) {
            return false;
        }

        if (\in_array($attribute, [self::LIST, self::CREATE], true)) {
            return true;
        }

        return $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return UserRole::MANAGER === $user->getRole();
    }
}
