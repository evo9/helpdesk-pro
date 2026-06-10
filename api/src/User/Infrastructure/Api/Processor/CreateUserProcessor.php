<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\User\Application\Command\CreateUser;
use App\User\Domain\Enum\UserRole;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Infrastructure\Api\Provider\UserStateProvider;
use App\User\Infrastructure\Api\Resource\UserResource;
use App\User\Infrastructure\Security\Voter\UserVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/** @implements ProcessorInterface<UserResource, UserResource> */
final class CreateUserProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
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
        $role = null !== $data->role
            ? UserRole::fromSecurityRole($data->role)
            : UserRole::REPORTER;

        try {
            $envelope = $this->messageBus->dispatch(new CreateUser(
                email: $data->email ?? '',
                plainPassword: $data->password ?? '',
                fullName: $data->fullName ?? '',
                role: $role,
            ));

            $stamp = $envelope->last(HandledStamp::class);
            \assert($stamp instanceof HandledStamp);
            $user = $stamp->getResult();
        } catch (HandlerFailedException $e) {
            $cause = $e->getPrevious();
            if ($cause instanceof UserAlreadyExistsException) {
                throw new UnprocessableEntityHttpException('A user with this email already exists.');
            }
            throw $e;
        }

        return $this->provider->toResource($user);
    }
}
