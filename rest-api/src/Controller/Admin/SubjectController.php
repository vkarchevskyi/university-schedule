<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\SubjectRequestDto;
use App\Service\SubjectService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/subjects')]
final class SubjectController extends AbstractAdminController
{
    public function __construct(private readonly SubjectService $subjects) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->respond($this->subjects->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] SubjectRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->subjects->create($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->respond(fn() => $this->subjects->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] SubjectRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->subjects->update($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return $this->noContent(fn() => $this->subjects->deleteById($id));
    }
}
