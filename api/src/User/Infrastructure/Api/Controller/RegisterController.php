<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Api\Controller;

use App\User\Application\Command\CreateUser;
use App\User\Domain\Exception\UserAlreadyExistsException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    #[Route('/api/auth/register', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $envelope = $this->messageBus->dispatch(new CreateUser(
                email: $data['email'] ?? '',
                plainPassword: $data['password'] ?? '',
                fullName: $data['fullName'] ?? '',
            ));

            $stamp = $envelope->last(HandledStamp::class);
            \assert($stamp instanceof HandledStamp);
            $user = $stamp->getResult();
        } catch (ValidationFailedException $e) {
            $errors = [];
            foreach ($e->getViolations() as $violation) {
                $errors[] = $violation->getPropertyPath().': '.$violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (HandlerFailedException $e) {
            $cause = $e->getPrevious();
            if ($cause instanceof UserAlreadyExistsException) {
                return new JsonResponse(
                    ['errors' => ['email: This email is already registered.']],
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }
            throw $e;
        }

        return new JsonResponse(['token' => $this->jwtManager->create($user)], Response::HTTP_CREATED);
    }
}
