<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\TeacherRequestDto;
use App\Service\Teacher\CreateTeacherService;
use App\Service\Teacher\DeleteTeacherService;
use App\Service\Teacher\GetTeacherService;
use App\Service\Teacher\ListTeachersService;
use App\Service\Teacher\UpdateTeacherService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/teachers')]
final class TeacherController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(ListTeachersService $teachers): JsonResponse
    {
        return $this->respond($teachers->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] TeacherRequestDto $request, CreateTeacherService $teachers): JsonResponse
    {
        return $this->respond(fn() => $teachers->handle($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id, GetTeacherService $teachers): JsonResponse
    {
        return $this->respond(fn() => $teachers->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] TeacherRequestDto $request, UpdateTeacherService $teachers): JsonResponse
    {
        return $this->respond(fn() => $teachers->handle($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, DeleteTeacherService $teachers): JsonResponse
    {
        return $this->noContent(fn() => $teachers->handle($id));
    }
}
