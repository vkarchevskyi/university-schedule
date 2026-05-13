<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\ExamScheduleEntryRequestDto;
use App\Dto\Admin\ExamScheduleQueryDto;
use App\Dto\Admin\ExamScheduleRequestDto;
use App\Service\ExamSchedule\CreateExamScheduleService;
use App\Service\ExamSchedule\DeleteExamScheduleService;
use App\Service\ExamSchedule\GetExamScheduleService;
use App\Service\ExamSchedule\ListExamSchedulesService;
use App\Service\ExamScheduleEntry\CreateExamScheduleEntryService;
use App\Service\ExamScheduleEntry\DeleteExamScheduleEntryService;
use App\Service\ExamScheduleEntry\UpdateExamScheduleEntryService;
use App\Service\ExamScheduleValidation\ValidateExamScheduleService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/exam-schedules')]
final class ExamScheduleController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(#[MapQueryString] ?ExamScheduleQueryDto $query, ListExamSchedulesService $examSchedules): JsonResponse
    {
        return $this->respond(fn() => $examSchedules->list($query));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json')] ExamScheduleRequestDto $request, CreateExamScheduleService $examSchedules): JsonResponse
    {
        return $this->respond(fn() => $examSchedules->handle($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id, GetExamScheduleService $examSchedules): JsonResponse
    {
        return $this->respond(fn() => $examSchedules->get($id));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, DeleteExamScheduleService $examSchedules): JsonResponse
    {
        return $this->noContent(fn() => $examSchedules->handle($id));
    }

    #[Route('/{id}/entries', methods: ['POST'])]
    public function createEntry(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] ExamScheduleEntryRequestDto $request, CreateExamScheduleEntryService $entries): JsonResponse
    {
        return $this->respond(fn() => $entries->handle($id, $request), Response::HTTP_CREATED);
    }

    #[Route('/{id}/entries/{entryId}', methods: ['PATCH'])]
    public function updateEntry(int $id, int $entryId, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] ExamScheduleEntryRequestDto $request, UpdateExamScheduleEntryService $entries): JsonResponse
    {
        return $this->respond(fn() => $entries->handle($id, $entryId, $request));
    }

    #[Route('/{id}/entries/{entryId}', methods: ['DELETE'])]
    public function deleteEntry(int $id, int $entryId, DeleteExamScheduleEntryService $entries): JsonResponse
    {
        return $this->noContent(fn() => $entries->handle($id, $entryId));
    }

    #[Route('/{id}/validate', methods: ['POST'])]
    public function validate(int $id, ValidateExamScheduleService $examSchedules): JsonResponse
    {
        return $this->respond(fn() => $examSchedules->handle($id));
    }
}
