<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\PublicScheduleQueryDto;
use App\Exception\ApiException;
use App\Service\PublicScheduleService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public')]
final class PublicScheduleController extends AbstractController
{
    public function __construct(private readonly PublicScheduleService $schedules) {}

    #[Route('/groups', methods: ['GET'])]
    public function groups(): JsonResponse
    {
        return $this->respond(fn() => $this->schedules->groups());
    }

    #[Route('/teachers', methods: ['GET'])]
    public function teachers(): JsonResponse
    {
        return $this->respond(fn() => $this->schedules->teachers());
    }

    #[Route('/rooms', methods: ['GET'])]
    public function rooms(): JsonResponse
    {
        return $this->respond(fn() => $this->schedules->rooms());
    }

    #[Route('/schedule', methods: ['GET'])]
    public function schedule(#[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] PublicScheduleQueryDto $query): JsonResponse
    {
        return $this->respond(fn() => $this->schedules->schedule($query));
    }

    private function respond(callable $operation): JsonResponse
    {
        try {
            return $this->json($operation());
        } catch (ApiException $exception) {
            return $this->json($exception->getBody(), $exception->getStatusCode());
        }
    }
}
