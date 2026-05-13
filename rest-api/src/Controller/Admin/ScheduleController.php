<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\ScheduleEntryRequestDto;
use App\Dto\Admin\ScheduleRequestDto;
use App\Service\LessonCard\ListLessonCardsService;
use App\Service\Schedule\CreateScheduleService;
use App\Service\Schedule\GetScheduleService;
use App\Service\ScheduleEntry\CreateScheduleEntryService;
use App\Service\ScheduleEntry\DeleteScheduleEntryService;
use App\Service\ScheduleEntry\UpdateScheduleEntryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/schedules')]
final class ScheduleController extends AbstractAdminController
{
    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json')] ScheduleRequestDto $request, CreateScheduleService $schedules): JsonResponse
    {
        return $this->respond(fn() => $schedules->handle($request), Response::HTTP_CREATED);
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
}
