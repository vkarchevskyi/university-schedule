<?php

declare(strict_types=1);

namespace App\Service\AI;

use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

final readonly class BuildTelegramIntentPromptService
{
    public function handle(string $text, \DateTimeImmutable $now, bool $repair = false): MessageBag
    {
        return new MessageBag(
            Message::forSystem($this->systemPrompt($now, $repair)),
            Message::ofUser($text),
        );
    }

    private function systemPrompt(\DateTimeImmutable $now, bool $repair): string
    {
        $lines = [
            'You parse Telegram messages for a university schedule bot.',
            'Return only structured JSON matching the requested schema.',
            'Never answer schedule questions from memory.',
            'Supported intents: get_schedule, subscribe, unsubscribe, help, unknown.',
            'get_schedule targetType may be group, teacher, or room.',
            'subscribe and unsubscribe targetType may be only group or teacher.',
            'Use unknown with a concise Ukrainian clarificationQuestion when the request is ambiguous.',
            'Use ISO dates. Current Kyiv date is ' . $now->format('Y-m-d') . '.',
            'For "today" set range=today and date=' . $now->format('Y-m-d') . '.',
            'For "tomorrow" set range=tomorrow and date=' . $now->modify('+1 day')->format('Y-m-d') . '.',
            'For a week request set range=week and weekStart to the Monday of that week.',
            'Examples:',
            'Покажи розклад КН-22 завтра -> intent=get_schedule, targetType=group, targetName=КН-22, range=tomorrow',
            'Підпиши мене на викладача John Doe -> intent=subscribe, targetType=teacher, targetName=John Doe',
            'Що в аудиторії Lab 1 цього тижня? -> intent=get_schedule, targetType=room, targetName=Lab 1, range=week',
            'Відпиши мене від КН-22 -> intent=unsubscribe, targetType=group, targetName=КН-22',
            'Допомога -> intent=help',
        ];

        if ($repair) {
            array_unshift($lines, 'The previous response could not be decoded. Return valid JSON only and follow the schema exactly.');
        }

        return implode("\n", $lines);
    }
}
