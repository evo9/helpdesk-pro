<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Application\Command\CreateUser;
use App\User\Application\Command\CreateUserHandler;
use App\User\Infrastructure\Api\Provider\UserStateProvider;
use App\User\Infrastructure\Api\Resource\UserResource;
use App\User\Infrastructure\Security\Voter\UserVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/** @implements ProcessorInterface<UserResource, UserResource> */
final class CreateUserProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CreateUserHandler $handler,
        private readonly UserStateProvider $provider,
        private readonly Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserResource
    {
        if (!$this->security->isGranted(UserVoter::CREATE)) {
            throw new AccessDeniedException();
        }

        /** @var UserResource $data */
        $user = ($this->handler)(new CreateUser(
            email: $data->email ?? '',
            plainPassword: $data->password ?? '',
            fullName: $data->fullName ?? '',
            role: $data->role ?? 'reporter',
        ));

        return $this->provider->toResource($user);
    }
}
