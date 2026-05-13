<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\TeacherUnavailabilityRequestDto;
use App\Service\TeacherUnavailabilityService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/teacher-unavailability')]
final class TeacherUnavailabilityController extends AbstractAdminController
{
    public function __construct(private readonly TeacherUnavailabilityService $teacherUnavailability) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->respond($this->teacherUnavailability->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] TeacherUnavailabilityRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->teacherUnavailability->create($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->respond(fn() => $this->teacherUnavailability->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] TeacherUnavailabilityRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->teacherUnavailability->update($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return $this->noContent(fn() => $this->teacherUnavailability->deleteById($id));
    }
}
