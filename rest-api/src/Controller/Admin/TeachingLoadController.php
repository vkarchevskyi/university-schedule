<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\TeachingLoadRequestDto;
use App\Service\TeachingLoad\CreateTeachingLoadService;
use App\Service\TeachingLoad\DeleteTeachingLoadService;
use App\Service\TeachingLoad\GetTeachingLoadService;
use App\Service\TeachingLoad\ListTeachingLoadsService;
use App\Service\TeachingLoad\UpdateTeachingLoadService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/teaching-loads')]
final class TeachingLoadController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(ListTeachingLoadsService $teachingLoads): JsonResponse
    {
        return $this->respond($teachingLoads->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] TeachingLoadRequestDto $request, CreateTeachingLoadService $teachingLoads): JsonResponse
    {
        return $this->respond(fn() => $teachingLoads->handle($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id, GetTeachingLoadService $teachingLoads): JsonResponse
    {
        return $this->respond(fn() => $teachingLoads->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] TeachingLoadRequestDto $request, UpdateTeachingLoadService $teachingLoads): JsonResponse
    {
        return $this->respond(fn() => $teachingLoads->handle($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, DeleteTeachingLoadService $teachingLoads): JsonResponse
    {
        return $this->noContent(fn() => $teachingLoads->handle($id));
    }
}
