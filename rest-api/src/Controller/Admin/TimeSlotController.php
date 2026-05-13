<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\TimeSlotRequestDto;
use App\Service\TimeSlotService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/time-slots')]
final class TimeSlotController extends AbstractAdminController
{
    public function __construct(private readonly TimeSlotService $timeSlots) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->respond($this->timeSlots->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] TimeSlotRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->timeSlots->create($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->respond(fn() => $this->timeSlots->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] TimeSlotRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->timeSlots->update($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return $this->noContent(fn() => $this->timeSlots->deleteById($id));
    }
}
