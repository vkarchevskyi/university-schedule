<?php

declare(strict_types=1);

namespace App\Service\Telegram;

final readonly class TelegramFlowState
{
    public function __construct(
        public string $action,
        public ?string $targetType,
        public int $page,
    ) {}
}
