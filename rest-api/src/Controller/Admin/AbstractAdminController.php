<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAdminController extends AbstractController
{
    protected function respond(callable $operation, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json($operation(), $status);
    }

    protected function noContent(callable $operation): JsonResponse
    {
        $operation();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
