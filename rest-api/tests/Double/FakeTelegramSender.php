<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Service\Telegram\TelegramSenderInterface;

final class FakeTelegramSender implements TelegramSenderInterface
{
    /** @var list<array{chatId: int, text: string}> */
    public static array $messages = [];

    public function sendMessage(int $chatId, string $text): void
    {
        self::$messages[] = ['chatId' => $chatId, 'text' => $text];
    }

    public static function reset(): void
    {
        self::$messages = [];
    }
}
