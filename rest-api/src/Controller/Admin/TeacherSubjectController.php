<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\TeacherSubjectRequestDto;
use App\Service\TeacherSubject\CreateTeacherSubjectService;
use App\Service\TeacherSubject\DeleteTeacherSubjectService;
use App\Service\TeacherSubject\GetTeacherSubjectService;
use App\Service\TeacherSubject\ListTeacherSubjectsService;
use App\Service\TeacherSubject\UpdateTeacherSubjectService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/teacher-subjects')]
final class TeacherSubjectController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(ListTeacherSubjectsService $teacherSubjects): JsonResponse
    {
        return $this->respond($teacherSubjects->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] TeacherSubjectRequestDto $request, CreateTeacherSubjectService $teacherSubjects): JsonResponse
    {
        return $this->respond(fn() => $teacherSubjects->handle($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id, GetTeacherSubjectService $teacherSubjects): JsonResponse
    {
        return $this->respond(fn() => $teacherSubjects->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] TeacherSubjectRequestDto $request, UpdateTeacherSubjectService $teacherSubjects): JsonResponse
    {
        return $this->respond(fn() => $teacherSubjects->handle($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, DeleteTeacherSubjectService $teacherSubjects): JsonResponse
    {
        return $this->noContent(fn() => $teacherSubjects->handle($id));
    }
}
