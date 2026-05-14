<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Exception\ApiException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class VerifyTelegramWebhookSecretService
{
    public function __construct(private string $webhookSecret) {}

    public function handle(Request $request): void
    {
        if ($this->webhookSecret === '') {
            throw ApiException::http(['error' => 'Telegram webhook secret is not configured.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!hash_equals($this->webhookSecret, (string) $request->headers->get('X-Telegram-Bot-Api-Secret-Token'))) {
            throw ApiException::http(['error' => 'Invalid Telegram webhook secret.'], Response::HTTP_UNAUTHORIZED);
        }
    }
}
