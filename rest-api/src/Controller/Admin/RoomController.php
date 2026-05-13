<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\RoomRequestDto;
use App\Service\RoomService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/rooms')]
final class RoomController extends AbstractAdminController
{
    public function __construct(private readonly RoomService $rooms) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->respond($this->rooms->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] RoomRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->rooms->create($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->respond(fn() => $this->rooms->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] RoomRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->rooms->update($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return $this->noContent(fn() => $this->rooms->deleteById($id));
    }
}
