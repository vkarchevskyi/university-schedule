<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Service\Telegram\TelegramNotificationPublisherInterface;

final class FakeTelegramNotificationPublisher implements TelegramNotificationPublisherInterface
{
    /** @var list<array<string, mixed>> */
    public static array $messages = [];

    public function publish(array $message): void
    {
        self::$messages[] = $message;
    }

    public static function reset(): void
    {
        self::$messages = [];
    }
}
