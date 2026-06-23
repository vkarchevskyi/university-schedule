<?php

declare(strict_types=1);

namespace App\Service\Telegram;

interface TelegramSenderInterface
{
    /** @param list<list<TelegramInlineButton>> $keyboard */
    public function sendMessage(int $chatId, string $text, array $keyboard = [], ?string $parseMode = null): void;

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): void;
}
