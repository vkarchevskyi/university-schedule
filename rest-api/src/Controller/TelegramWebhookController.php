<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ApiException;
use App\Service\Telegram\HandleTelegramWebhookService;
use App\Service\Telegram\VerifyTelegramWebhookSecretService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/telegram')]
final class TelegramWebhookController extends AbstractController
{
    #[Route('/webhook', methods: ['POST'])]
    public function webhook(Request $request, VerifyTelegramWebhookSecretService $secret, HandleTelegramWebhookService $handler): JsonResponse
    {
        try {
            $secret->handle($request);
            $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);

            $handler->handle($this->objectPayload($payload));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (\JsonException) {
            return $this->json(['errors' => ['payload' => 'Expected valid JSON.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (ApiException $exception) {
            return $this->json($exception->getBody(), $exception->getStatusCode());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function objectPayload(mixed $payload): array
    {
        if (!is_array($payload)) {
            throw ApiException::validation(['payload' => 'Expected Telegram update object.']);
        }

        $result = [];
        foreach ($payload as $key => $value) {
            if (!is_string($key)) {
                throw ApiException::validation(['payload' => 'Expected Telegram update object.']);
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
