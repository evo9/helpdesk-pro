<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Api\Provider;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Api\Resource\UserResource;
use App\User\Infrastructure\Security\Voter\UserVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

/** @implements ProviderInterface<UserResource> */
final class UserStateProvider implements ProviderInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
        private readonly Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array
    {
        if ($operation instanceof CollectionOperationInterface) {
            if (!$this->security->isGranted(UserVoter::LIST)) {
                throw new AccessDeniedException();
            }

            return array_map(
                fn (User $u) => $this->toResource($u),
                $this->userRepo->findAll(),
            );
        }

        $user = $this->userRepo->findById(Uuid::fromString((string) ($uriVariables['id'] ?? '')));
        if (null === $user) {
            throw new NotFoundHttpException('User not found.');
        }

        if (!$this->security->isGranted(UserVoter::VIEW, $user)) {
            throw new AccessDeniedException();
        }

        return $this->toResource($user);
    }

    public function toResource(User $user): UserResource
    {
        $resource = new UserResource();
        $resource->id = (string) $user->getId();
        $resource->email = $user->getEmail();
        $resource->fullName = $user->getFullName();
        $resource->role = $user->getRole()->value;
        $resource->isActive = $user->isActive();
        $resource->createdAt = $user->getCreatedAt();

        return $resource;
    }
}
