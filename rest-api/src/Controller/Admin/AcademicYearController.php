<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\AcademicYearRequestDto;
use App\Service\AcademicYear\CreateAcademicYearService;
use App\Service\AcademicYear\DeleteAcademicYearService;
use App\Service\AcademicYear\GetAcademicYearService;
use App\Service\AcademicYear\ListAcademicYearsService;
use App\Service\AcademicYear\UpdateAcademicYearService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/academic-years')]
final class AcademicYearController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(ListAcademicYearsService $academicYears): JsonResponse
    {
        return $this->respond($academicYears->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] AcademicYearRequestDto $request, CreateAcademicYearService $academicYears): JsonResponse
    {
        return $this->respond(fn() => $academicYears->handle($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id, GetAcademicYearService $academicYears): JsonResponse
    {
        return $this->respond(fn() => $academicYears->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] AcademicYearRequestDto $request, UpdateAcademicYearService $academicYears): JsonResponse
    {
        return $this->respond(fn() => $academicYears->handle($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, DeleteAcademicYearService $academicYears): JsonResponse
    {
        return $this->noContent(fn() => $academicYears->handle($id));
    }
}
