<?php

declare(strict_types=1);

namespace App\Service\Telegram;

final readonly class TelegramTarget
{
    public function __construct(
        public string $type,
        public int $id,
        public string $label,
    ) {}
}
