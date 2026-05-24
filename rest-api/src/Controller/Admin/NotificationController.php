<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\Notification\CreateWebSocketTicketService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/notifications')]
final class NotificationController extends AbstractAdminController
{
    #[Route('/ws-ticket', methods: ['POST'])]
    public function createWebSocketTicket(CreateWebSocketTicketService $tickets): JsonResponse
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        return $this->respond(fn() => $tickets->create($user));
    }
}
