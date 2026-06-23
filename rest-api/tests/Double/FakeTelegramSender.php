<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Service\Telegram\TelegramSenderInterface;

final class FakeTelegramSender implements TelegramSenderInterface
{
    /** @var list<array{chatId: int, text: string, keyboard: list<list<\App\Service\Telegram\TelegramInlineButton>>, parseMode: string|null}> */
    public static array $messages = [];

    /** @param list<list<\App\Service\Telegram\TelegramInlineButton>> $keyboard */
    public function sendMessage(int $chatId, string $text, array $keyboard = [], ?string $parseMode = null): void
    {
        self::$messages[] = ['chatId' => $chatId, 'text' => $text, 'keyboard' => $keyboard, 'parseMode' => $parseMode];
    }

    /** @var list<array{id: string, text: string|null}> */
    public static array $callbackAnswers = [];

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): void
    {
        self::$callbackAnswers[] = ['id' => $callbackQueryId, 'text' => $text];
    }

    public static function reset(): void
    {
        self::$messages = [];
        self::$callbackAnswers = [];
    }
}
