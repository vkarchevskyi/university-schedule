<?php

declare(strict_types=1);

namespace App\Service\AI;

interface TelegramIntentParserInterface
{
    public function handle(string $text): TelegramIntent;
}
