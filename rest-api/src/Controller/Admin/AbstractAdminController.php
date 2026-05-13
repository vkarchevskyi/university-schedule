<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAdminController extends AbstractController
{
    protected function respond(callable $operation, int $status = Response::HTTP_OK): JsonResponse
    {
        try {
            return $this->json($operation(), $status);
        } catch (ApiException $exception) {
            return $this->json($exception->getBody(), $exception->getStatusCode());
        }
    }

    protected function noContent(callable $operation): JsonResponse
    {
        try {
            $operation();

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (ApiException $exception) {
            return $this->json($exception->getBody(), $exception->getStatusCode());
        }
    }
}
