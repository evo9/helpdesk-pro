<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Api\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController
{
    #[Route('/api/auth/login', methods: ['POST'])]
    public function __invoke(): JsonResponse
    {
        // Intercepted by Symfony's json_login security authenticator before this runs
        throw new \LogicException('This should never be reached.');
    }
}
