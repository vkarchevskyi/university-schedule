<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Admin;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth')]
final class AuthController extends AbstractController
{
    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        throw new \LogicException('This route is handled by the security firewall.');
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(#[CurrentUser] Admin $admin): JsonResponse
    {
        return $this->json([
            'admin' => [
                'id' => $admin->getId(),
                'firstName' => $admin->getFirstName(),
                'lastName' => $admin->getLastName(),
                'email' => $admin->getEmail(),
            ],
        ]);
    }
}
