<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\TeachingLoadRequestDto;
use App\Service\TeachingLoadService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/teaching-loads')]
final class TeachingLoadController extends AbstractAdminController
{
    public function __construct(private readonly TeachingLoadService $teachingLoads) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->respond($this->teachingLoads->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] TeachingLoadRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->teachingLoads->create($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->respond(fn() => $this->teachingLoads->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] TeachingLoadRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->teachingLoads->update($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return $this->noContent(fn() => $this->teachingLoads->deleteById($id));
    }
}
