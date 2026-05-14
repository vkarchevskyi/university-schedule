<?php

declare(strict_types=1);

namespace App\Service\Telegram;

interface TelegramSenderInterface
{
    public function sendMessage(int $chatId, string $text): void;
}
