<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ActionLog\ListActionLogsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/action-logs')]
final class ActionLogController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(ListActionLogsService $logs): JsonResponse
    {
        return $this->respond(fn() => $logs->list());
    }
}
