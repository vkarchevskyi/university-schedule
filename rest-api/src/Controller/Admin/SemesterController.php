<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\SemesterRequestDto;
use App\Service\SemesterService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/semesters')]
final class SemesterController extends AbstractAdminController
{
    public function __construct(private readonly SemesterService $semesters) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->respond($this->semesters->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] SemesterRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->semesters->create($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->respond(fn() => $this->semesters->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] SemesterRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->semesters->update($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return $this->noContent(fn() => $this->semesters->deleteById($id));
    }
}
