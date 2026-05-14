<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Exception\ApiException;
use SergiX44\Nutgram\Nutgram;
use Symfony\Component\HttpFoundation\Response;

final readonly class NutgramTelegramSender implements TelegramSenderInterface
{
    public function __construct(private string $telegramBotToken) {}

    public function sendMessage(int $chatId, string $text): void
    {
        if ($this->telegramBotToken === '') {
            throw ApiException::http(['error' => 'Telegram bot token is not configured.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $bot = new Nutgram($this->telegramBotToken);
        $bot->sendMessage($text, $chatId);
    }
}
