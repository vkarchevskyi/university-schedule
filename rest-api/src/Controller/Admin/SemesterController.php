<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\SemesterRequestDto;
use App\Service\Semester\CreateSemesterService;
use App\Service\Semester\DeleteSemesterService;
use App\Service\Semester\GetSemesterService;
use App\Service\Semester\ListSemestersService;
use App\Service\Semester\UpdateSemesterService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/semesters')]
final class SemesterController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(ListSemestersService $semesters): JsonResponse
    {
        return $this->respond($semesters->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] SemesterRequestDto $request, CreateSemesterService $semesters): JsonResponse
    {
        return $this->respond(fn() => $semesters->handle($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id, GetSemesterService $semesters): JsonResponse
    {
        return $this->respond(fn() => $semesters->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] SemesterRequestDto $request, UpdateSemesterService $semesters): JsonResponse
    {
        return $this->respond(fn() => $semesters->handle($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, DeleteSemesterService $semesters): JsonResponse
    {
        return $this->noContent(fn() => $semesters->handle($id));
    }
}
