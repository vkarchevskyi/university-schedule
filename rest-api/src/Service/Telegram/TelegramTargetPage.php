<?php

declare(strict_types=1);

namespace App\Service\Telegram;

final readonly class TelegramTargetPage
{
    /**
     * @param list<TelegramTargetListItem> $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public bool $hasPrevious,
        public bool $hasNext,
    ) {}
}
