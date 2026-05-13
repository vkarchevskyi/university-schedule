<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\Admin\GroupRequestDto;
use App\Service\Group\CreateGroupService;
use App\Service\Group\DeleteGroupService;
use App\Service\Group\GetGroupService;
use App\Service\Group\ListGroupsService;
use App\Service\Group\UpdateGroupService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/groups')]
final class GroupController extends AbstractAdminController
{
    #[Route('', methods: ['GET'])]
    public function list(ListGroupsService $groups): JsonResponse
    {
        return $this->respond($groups->list(...));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationGroups: ['create'])] GroupRequestDto $request, CreateGroupService $groups): JsonResponse
    {
        return $this->respond(fn() => $groups->handle($request), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id, GetGroupService $groups): JsonResponse
    {
        return $this->respond(fn() => $groups->get($id));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload(acceptFormat: 'json', validationGroups: ['update'])] GroupRequestDto $request, UpdateGroupService $groups): JsonResponse
    {
        return $this->respond(fn() => $groups->handle($id, $request));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, DeleteGroupService $groups): JsonResponse
    {
        return $this->noContent(fn() => $groups->handle($id));
    }
}
