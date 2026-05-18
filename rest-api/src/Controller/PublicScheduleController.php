<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\PublicScheduleQueryDto;
use App\Service\PublicSchedule\GetPublicScheduleService;
use App\Service\PublicSchedule\ListPublicGroupsService;
use App\Service\PublicSchedule\ListPublicRoomsService;
use App\Service\PublicSchedule\ListPublicTeachersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public')]
final class PublicScheduleController extends AbstractController
{
    #[Route('/groups', methods: ['GET'])]
    public function groups(ListPublicGroupsService $groups): JsonResponse
    {
        return $this->respond(fn() => $groups->list());
    }

    #[Route('/teachers', methods: ['GET'])]
    public function teachers(ListPublicTeachersService $teachers): JsonResponse
    {
        return $this->respond(fn() => $teachers->list());
    }

    #[Route('/rooms', methods: ['GET'])]
    public function rooms(ListPublicRoomsService $rooms): JsonResponse
    {
        return $this->respond(fn() => $rooms->list());
    }

    #[Route('/schedule', methods: ['GET'])]
    public function schedule(#[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] PublicScheduleQueryDto $query, GetPublicScheduleService $schedules): JsonResponse
    {
        return $this->respond(fn() => $schedules->get($query));
    }

    private function respond(callable $operation): JsonResponse
    {
        return $this->json($operation());
    }
}
