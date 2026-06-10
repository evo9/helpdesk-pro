<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Api\Controller;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use App\User\Infrastructure\Doctrine\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegisterController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/api/auth/register', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $violations = $this->validator->validate($data, new Assert\Collection([
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'password' => [new Assert\NotBlank(), new Assert\Length(min: 8)],
            'fullName' => [new Assert\NotBlank(), new Assert\Length(min: 2)],
        ]));

        if (\count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = $v->getPropertyPath().': '.$v->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (null !== $this->userRepository->findByEmail($data['email'])) {
            return new JsonResponse(
                ['errors' => ['email: This email is already registered.']],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user = new User(
            email: $data['email'],
            passwordHash: '',
            fullName: $data['fullName'],
            role: UserRole::REPORTER,
        );
        $user->updatePassword($this->passwordHasher->hashPassword($user, $data['password']));

        $this->userRepository->save($user);

        return new JsonResponse(['token' => $this->jwtManager->create($user)], Response::HTTP_CREATED);
    }
}
