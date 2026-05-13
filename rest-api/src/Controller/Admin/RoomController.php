<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\RoomRequestDto;
use App\Service\Room\CreateRoomService;
use App\Service\Room\DeleteRoomService;
use App\Service\Room\GetRoomService;
use App\Service\Room\ListRoomsService;
use App\Service\Room\UpdateRoomService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/rooms')]
final class RoomController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(ListRoomsService $rooms): JsonResponse
    {
        return $this->respond($rooms->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] RoomRequestDto $request, CreateRoomService $rooms): JsonResponse
    {
        return $this->respond(fn() => $rooms->handle($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id, GetRoomService $rooms): JsonResponse
    {
        return $this->respond(fn() => $rooms->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] RoomRequestDto $request, UpdateRoomService $rooms): JsonResponse
    {
        return $this->respond(fn() => $rooms->handle($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, DeleteRoomService $rooms): JsonResponse
    {
        return $this->noContent(fn() => $rooms->handle($id));
    }
}
