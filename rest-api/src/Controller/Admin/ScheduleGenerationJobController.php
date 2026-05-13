<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ScheduleGeneration\GetScheduleGenerationJobService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/generation-jobs')]
final class ScheduleGenerationJobController extends AbstractAdminController
{
    #[Route('/{jobId}', methods: ['GET'])]
    public function get(string $jobId, GetScheduleGenerationJobService $jobs): JsonResponse
    {
        return $this->respond(fn() => $jobs->get($jobId));
    }
}
