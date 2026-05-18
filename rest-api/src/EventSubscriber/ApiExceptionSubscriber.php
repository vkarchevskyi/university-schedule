<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\ApiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        $validation = $exception->getPrevious();

        if ($exception instanceof ApiException) {
            $event->setResponse(new JsonResponse($exception->getBody(), $exception->getStatusCode()));

            return;
        }

        if ($exception instanceof \JsonException) {
            $event->setResponse(new JsonResponse([
                'type' => 'https://university-schedule.local/problems/validation-error',
                'title' => 'Validation failed',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'violations' => [
                    [
                        'propertyPath' => 'payload',
                        'message' => 'Expected valid JSON.',
                    ],
                ],
                'errors' => [
                    'payload' => 'Expected valid JSON.',
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY));

            return;
        }

        if ($validation instanceof ValidationFailedException) {
            $event->setResponse($this->validationResponse($validation));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $event->setResponse(new JsonResponse([
                'type' => $this->typeForStatus($status),
                'title' => Response::$statusTexts[$status] ?? 'HTTP error',
                'status' => $status,
                'detail' => $this->detail($request, $exception),
            ], $status, $exception->getHeaders()));
        }
    }

    /** @return array<string, string> */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    private function validationResponse(ValidationFailedException $exception): JsonResponse
    {
        $violations = [];
        $errors = [];

        foreach ($exception->getViolations() as $violation) {
            $propertyPath = $violation->getPropertyPath();
            $message = $violation->getMessage();

            $violations[] = [
                'propertyPath' => $propertyPath,
                'message' => $message,
            ];

            $errors[$propertyPath ?: 'payload'] = $message;
        }

        return new JsonResponse([
            'type' => 'https://university-schedule.local/problems/validation-error',
            'title' => 'Validation failed',
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'violations' => $violations,
            'errors' => $errors,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function detail(Request $request, HttpExceptionInterface $exception): string
    {
        if ($request->server->get('APP_ENV') === 'prod') {
            return Response::$statusTexts[$exception->getStatusCode()] ?? 'HTTP error';
        }

        return $exception->getMessage();
    }

    private function typeForStatus(int $status): string
    {
        return match ($status) {
            Response::HTTP_BAD_REQUEST => 'https://university-schedule.local/problems/bad-request',
            Response::HTTP_UNSUPPORTED_MEDIA_TYPE => 'https://university-schedule.local/problems/unsupported-media-type',
            Response::HTTP_UNPROCESSABLE_ENTITY => 'https://university-schedule.local/problems/validation-error',
            default => 'about:blank',
        };
    }
}
