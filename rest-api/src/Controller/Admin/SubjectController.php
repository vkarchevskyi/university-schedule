<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\SubjectRequestDto;
use App\Service\Subject\CreateSubjectService;
use App\Service\Subject\DeleteSubjectService;
use App\Service\Subject\GetSubjectService;
use App\Service\Subject\ListSubjectsService;
use App\Service\Subject\UpdateSubjectService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/subjects')]
final class SubjectController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(ListSubjectsService $subjects): JsonResponse
    {
        return $this->respond($subjects->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] SubjectRequestDto $request, CreateSubjectService $subjects): JsonResponse
    {
        return $this->respond(fn() => $subjects->handle($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id, GetSubjectService $subjects): JsonResponse
    {
        return $this->respond(fn() => $subjects->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] SubjectRequestDto $request, UpdateSubjectService $subjects): JsonResponse
    {
        return $this->respond(fn() => $subjects->handle($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, DeleteSubjectService $subjects): JsonResponse
    {
        return $this->noContent(fn() => $subjects->handle($id));
    }
}
