<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\TimeSlotRequestDto;
use App\Service\TimeSlot\CreateTimeSlotService;
use App\Service\TimeSlot\DeleteTimeSlotService;
use App\Service\TimeSlot\GetTimeSlotService;
use App\Service\TimeSlot\ListTimeSlotsService;
use App\Service\TimeSlot\UpdateTimeSlotService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/time-slots')]
final class TimeSlotController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(ListTimeSlotsService $timeSlots): JsonResponse
    {
        return $this->respond($timeSlots->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] TimeSlotRequestDto $request, CreateTimeSlotService $timeSlots): JsonResponse
    {
        return $this->respond(fn() => $timeSlots->handle($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id, GetTimeSlotService $timeSlots): JsonResponse
    {
        return $this->respond(fn() => $timeSlots->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] TimeSlotRequestDto $request, UpdateTimeSlotService $timeSlots): JsonResponse
    {
        return $this->respond(fn() => $timeSlots->handle($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, DeleteTimeSlotService $timeSlots): JsonResponse
    {
        return $this->noContent(fn() => $timeSlots->handle($id));
    }
}
