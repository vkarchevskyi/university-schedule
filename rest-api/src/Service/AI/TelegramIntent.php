<?php

declare(strict_types=1);

namespace App\Service\AI;

use Symfony\Component\Validator\Constraints as Assert;

final class TelegramIntent
{
    #[Assert\Choice(choices: ['get_schedule', 'subscribe', 'unsubscribe', 'help', 'unknown'])]
    public string $intent = 'unknown';

    #[Assert\Range(min: 0, max: 1)]
    public float $confidence = 0.0;

    #[Assert\Choice(choices: ['group', 'teacher', 'room', null])]
    public ?string $targetType = null;

    public ?string $targetName = null;

    #[Assert\Date]
    public ?string $date = null;

    #[Assert\Date]
    public ?string $weekStart = null;

    #[Assert\Choice(choices: ['today', 'tomorrow', 'week', 'date', null])]
    public ?string $range = null;

    public ?string $clarificationQuestion = null;

    public static function unknown(?string $clarificationQuestion = null): self
    {
        $intent = new self();
        $intent->intent = 'unknown';
        $intent->confidence = 0.0;
        $intent->clarificationQuestion = $clarificationQuestion;

        return $intent;
    }
}
