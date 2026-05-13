<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\TeacherRequestDto;
use App\Service\TeacherService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/teachers')]
final class TeacherController extends AbstractAdminController
{
    public function __construct(private readonly TeacherService $teachers) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->respond($this->teachers->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] TeacherRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->teachers->create($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->respond(fn() => $this->teachers->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] TeacherRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->teachers->update($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return $this->noContent(fn() => $this->teachers->deleteById($id));
    }
}
