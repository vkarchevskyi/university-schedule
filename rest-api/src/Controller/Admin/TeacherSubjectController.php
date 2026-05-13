<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\TeacherSubjectRequestDto;
use App\Service\TeacherSubjectService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/teacher-subjects')]
final class TeacherSubjectController extends AbstractAdminController
{
    public function __construct(private readonly TeacherSubjectService $teacherSubjects) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->respond($this->teacherSubjects->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] TeacherSubjectRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->teacherSubjects->create($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->respond(fn() => $this->teacherSubjects->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] TeacherSubjectRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->teacherSubjects->update($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return $this->noContent(fn() => $this->teacherSubjects->deleteById($id));
    }
}
