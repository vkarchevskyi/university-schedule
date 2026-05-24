<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ExamScheduleGeneration\GetExamScheduleGenerationJobService;
use App\Service\ExamScheduleGeneration\ListExamScheduleGenerationJobsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/exam-schedule-generation-jobs')]
final class ExamScheduleGenerationJobController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(ListExamScheduleGenerationJobsService $jobs): JsonResponse
    {
        return $this->respond(fn() => $jobs->list());
    }

    #[Route('/{jobId}', methods: ['GET'])]
    public function get(string $jobId, GetExamScheduleGenerationJobService $jobs): JsonResponse
    {
        return $this->respond(fn() => $jobs->get($jobId));
    }
}
