<?php

declare(strict_types=1);

namespace App\Service\Telegram;

interface TelegramNotificationPublisherInterface
{
    /** @param array<string, mixed> $message */
    public function publish(array $message): void;
}
