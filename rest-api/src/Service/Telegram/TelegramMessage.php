<?php

declare(strict_types=1);

namespace App\Service\Telegram;

final readonly class TelegramMessage
{
    /**
     * @param list<list<TelegramInlineButton>> $keyboard
     */
    public function __construct(
        public string $text,
        public array $keyboard = [],
        public ?string $parseMode = null,
    ) {}
}
