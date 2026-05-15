<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Service\AI\AiParserUnavailableException;
use App\Service\AI\TelegramIntent;
use App\Service\AI\TelegramIntentParserInterface;

final class FakeTelegramIntentParser implements TelegramIntentParserInterface
{
    public static ?TelegramIntent $intent = null;
    public static bool $throws = false;
    public static int $calls = 0;

    public function handle(string $text): TelegramIntent
    {
        self::$calls++;

        if (self::$throws) {
            throw new AiParserUnavailableException('Unavailable.');
        }

        return self::$intent ?? TelegramIntent::unknown('Уточніть, будь ласка, запит.');
    }

    public static function reset(): void
    {
        self::$intent = null;
        self::$throws = false;
        self::$calls = 0;
    }
}
