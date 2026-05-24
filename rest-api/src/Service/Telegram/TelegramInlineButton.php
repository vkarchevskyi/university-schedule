<?php

declare(strict_types=1);

namespace App\Service\Telegram;

final readonly class TelegramInlineButton
{
    public function __construct(
        public string $text,
        public string $callbackData,
    ) {}
}
