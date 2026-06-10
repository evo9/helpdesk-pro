<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Application\Command\ChangeUserRole;
use App\User\Application\Command\ChangeUserRoleHandler;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Api\Provider\UserStateProvider;
use App\User\Infrastructure\Api\Resource\UserResource;
use App\User\Infrastructure\Security\Voter\UserVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Uid\Uuid;

/** @implements ProcessorInterface<UserResource, UserResource> */
final class UpdateUserProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ChangeUserRoleHandler $changeRoleHandler,
        private readonly UserRepositoryInterface $userRepo,
        private readonly UserStateProvider $provider,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserResource
    {
        $userId = (string) ($uriVariables['id'] ?? '');

        $user = $this->userRepo->findById(Uuid::fromString($userId))
            ?? throw new NotFoundHttpException('User not found.');

        if (!$this->security->isGranted(UserVoter::UPDATE, $user)) {
            throw new AccessDeniedException();
        }

        /** @var UserResource $data */
        /** @var UserResource $previous */
        $previous = $context['previous_data'];

        if (null !== $data->role && $data->role !== $previous->role) {
            ($this->changeRoleHandler)(new ChangeUserRole($userId, $data->role));
        }

        if (null !== $data->isActive && $data->isActive !== $previous->isActive) {
            $data->isActive ? $user->activate() : $user->deactivate();
            $this->userRepo->save($user);
        }

        $user = $this->userRepo->findById(Uuid::fromString($userId))
            ?? throw new NotFoundHttpException('User not found.');

        return $this->provider->toResource($user);
    }
}
