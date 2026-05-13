<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\GroupRequestDto;
use App\Service\GroupService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/groups')]
final class GroupController extends AbstractAdminController
{
    public function __construct(private readonly GroupService $groups) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->respond($this->groups->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] GroupRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->groups->create($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        return $this->respond(fn() => $this->groups->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] GroupRequestDto $request): JsonResponse
    {
        return $this->respond(fn() => $this->groups->update($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        return $this->noContent(fn() => $this->groups->deleteById($id));
    }
}
