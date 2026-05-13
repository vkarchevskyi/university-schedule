<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\TeacherUnavailabilityRequestDto;
use App\Service\TeacherUnavailability\CreateTeacherUnavailabilityService;
use App\Service\TeacherUnavailability\DeleteTeacherUnavailabilityService;
use App\Service\TeacherUnavailability\GetTeacherUnavailabilityService;
use App\Service\TeacherUnavailability\ListTeacherUnavailabilityService;
use App\Service\TeacherUnavailability\UpdateTeacherUnavailabilityService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/teacher-unavailability')]
final class TeacherUnavailabilityController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(ListTeacherUnavailabilityService $teacherUnavailability): JsonResponse
    {
        return $this->respond($teacherUnavailability->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] TeacherUnavailabilityRequestDto $request, CreateTeacherUnavailabilityService $teacherUnavailability): JsonResponse
    {
        return $this->respond(fn() => $teacherUnavailability->handle($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id, GetTeacherUnavailabilityService $teacherUnavailability): JsonResponse
    {
        return $this->respond(fn() => $teacherUnavailability->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] TeacherUnavailabilityRequestDto $request, UpdateTeacherUnavailabilityService $teacherUnavailability): JsonResponse
    {
        return $this->respond(fn() => $teacherUnavailability->handle($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, DeleteTeacherUnavailabilityService $teacherUnavailability): JsonResponse
    {
        return $this->noContent(fn() => $teacherUnavailability->handle($id));
    }
}
