<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Api\Controller;

use App\User\Domain\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class RefreshController
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly Security $security,
    ) {
    }

    #[Route('/api/auth/refresh', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer');
        }

        return new JsonResponse(['token' => $this->jwtManager->create($user)]);
    }
}
