<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\AcademicYearRequestDto;
use App\Service\AcademicYearService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/academic-years')]
final class AcademicYearController extends AbstractAdminController
{
    public function __construct(private readonly AcademicYearService $academicYears) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->respond($this->academicYears->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] AcademicYearRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->academicYears->create($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->respond(fn() => $this->academicYears->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] AcademicYearRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->academicYears->update($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return $this->noContent(fn() => $this->academicYears->deleteById($id));
    }
}
