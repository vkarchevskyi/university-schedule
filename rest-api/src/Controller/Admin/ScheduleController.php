<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\ScheduleEntryRequestDto;
use App\Dto\Admin\ScheduleGenerationRequestDto;
use App\Dto\Admin\ScheduleQueryDto;
use App\Dto\Admin\ScheduleRequestDto;
use App\Service\LessonCard\ListLessonCardsService;
use App\Service\Schedule\CreateScheduleService;
use App\Service\Schedule\DuplicateScheduleService;
use App\Service\Schedule\GetScheduleService;
use App\Service\Schedule\ListSchedulesService;
use App\Service\Schedule\PublishScheduleService;
use App\Service\ScheduleGeneration\CreateScheduleGenerationJobService;
use App\Service\ScheduleEntry\CreateScheduleEntryService;
use App\Service\ScheduleEntry\DeleteScheduleEntryService;
use App\Service\ScheduleEntry\UpdateScheduleEntryService;
use App\Service\ScheduleValidation\ValidateScheduleService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/schedules')]
final class ScheduleController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(#[MapQueryString] ?ScheduleQueryDto $query, ListSchedulesService $schedules): JsonResponse
    {
        return $this->respond(fn() => $schedules->list($query));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json')] ScheduleRequestDto $request, CreateScheduleService $schedules): JsonResponse
    {
        return $this->respond(fn() => $schedules->handle($request), Response::HTTP_CREATED);
    }

    #[Route('/generate', methods: ['POST'])]
    public function generate(#[MapRequestPayload(acceptFormat: 'json')] ScheduleGenerationRequestDto $request, CreateScheduleGenerationJobService $jobs): JsonResponse
    {
        return $this->respond(fn() => $jobs->handle($request), Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id, GetScheduleService $schedules): JsonResponse
    {
        return $this->respond(fn() => $schedules->get($id));
    }

    #[Route('/{id}/entries', methods: ['POST'])]
    public function createEntry(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] ScheduleEntryRequestDto $request, CreateScheduleEntryService $entries): JsonResponse
    {
        return $this->respond(fn() => $entries->handle($id, $request), Response::HTTP_CREATED);
    }

    #[Route('/{id}/entries/{entryId}', methods: ['PATCH'])]
    public function updateEntry(int $id, int $entryId, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] ScheduleEntryRequestDto $request, UpdateScheduleEntryService $entries): JsonResponse
    {
        return $this->respond(fn() => $entries->handle($id, $entryId, $request));
    }

    #[Route('/{id}/entries/{entryId}', methods: ['DELETE'])]
    public function deleteEntry(int $id, int $entryId, DeleteScheduleEntryService $entries): JsonResponse
    {
        return $this->noContent(fn() => $entries->handle($id, $entryId));
    }

    #[Route('/{id}/lesson-cards', methods: ['GET'])]
    public function lessonCards(int $id, ListLessonCardsService $lessonCards): JsonResponse
    {
        return $this->respond(fn() => $lessonCards->list($id));
    }

    #[Route('/{id}/validate', methods: ['POST'])]
    public function validate(int $id, ValidateScheduleService $schedules): JsonResponse
    {
        return $this->respond(fn() => $schedules->handle($id));
    }

    #[Route('/{id}/duplicate', methods: ['POST'])]
    public function duplicate(int $id, DuplicateScheduleService $schedules): JsonResponse
    {
        return $this->respond(fn() => $schedules->handle($id), Response::HTTP_CREATED);
    }

    #[Route('/{id}/publish', methods: ['POST'])]
    public function publish(int $id, PublishScheduleService $schedules): JsonResponse
    {
        return $this->respond(fn() => $schedules->handle($id));
    }
}
