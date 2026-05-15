<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Dto\PublicScheduleQueryDto;
use App\Exception\ApiException;
use App\Service\AI\AiParserUnavailableException;
use App\Service\AI\TelegramIntent;
use App\Service\AI\TelegramIntentParserInterface;
use App\Service\PublicSchedule\GetPublicScheduleService;

final readonly class HandleTelegramWebhookService
{
    private const AI_CONFIDENCE_THRESHOLD = 0.70;

    public function __construct(
        private TelegramSenderInterface $sender,
        private ResolveTelegramTargetService $targets,
        private CreateTelegramSubscriptionService $subscriptions,
        private DeleteTelegramSubscriptionService $unsubscriptions,
        private GetPublicScheduleService $publicSchedules,
        private FormatTelegramScheduleService $formatter,
        private TelegramIntentParserInterface $intentParser,
    ) {}

    /** @param array<string, mixed> $payload */
    public function handle(array $payload): void
    {
        $message = $payload['message'] ?? null;
        if (!is_array($message)) {
            return;
        }

        $chat = $message['chat'] ?? null;
        $chatId = is_array($chat) && is_int($chat['id'] ?? null) ? $chat['id'] : null;
        $text = is_string($message['text'] ?? null) ? trim($message['text']) : '';

        if ($chatId === null || $text === '') {
            return;
        }

        try {
            $response = $this->response($chatId, $text);
        } catch (ApiException $exception) {
            $response = $this->validationMessage($exception);
        }

        $this->sender->sendMessage($chatId, $response);
    }

    private function response(int $chatId, string $text): string
    {
        if (!str_starts_with($text, '/')) {
            return $this->freeText($chatId, $text);
        }

        $parts = preg_split('/\s+/', $text, 3) ?: [];
        $command = strtolower($parts[0] ?? '');

        return match ($command) {
            '/start', '/help' => $this->help(),
            '/schedule' => $this->schedule($parts),
            '/subscribe' => $this->subscribe($chatId, $parts),
            '/unsubscribe' => $this->unsubscribe($chatId, $parts),
            default => 'Команду не розпізнано. Надішліть /start, щоб побачити доступні команди.',
        };
    }

    private function freeText(int $chatId, string $text): string
    {
        try {
            $intent = $this->intentParser->handle($text);
        } catch (AiParserUnavailableException) {
            return 'AI-помічник тимчасово недоступний. Спробуйте пізніше або скористайтеся командою /schedule.';
        }

        if ($intent->confidence < self::AI_CONFIDENCE_THRESHOLD || $intent->intent === 'unknown') {
            return $intent->clarificationQuestion ?? 'Уточніть, будь ласка, групу, викладача або аудиторію.';
        }

        return match ($intent->intent) {
            'get_schedule' => $this->scheduleFromIntent($intent),
            'subscribe' => $this->subscribeFromIntent($chatId, $intent),
            'unsubscribe' => $this->unsubscribeFromIntent($chatId, $intent),
            'help' => $this->help(),
            default => $intent->clarificationQuestion ?? 'Уточніть, будь ласка, що саме потрібно зробити.',
        };
    }

    /** @param list<string> $parts */
    private function schedule(array $parts): string
    {
        $target = $this->targetFromParts($parts);
        $weekStart = $this->currentWeekStart();
        $schedule = $this->publicSchedules->get(new PublicScheduleQueryDto($target->type, $target->id, $weekStart));

        return $this->formatter->handle($schedule);
    }

    private function scheduleFromIntent(TelegramIntent $intent): string
    {
        $target = $this->targetFromIntent($intent, allowRoom: true);
        $schedule = $this->publicSchedules->get(new PublicScheduleQueryDto($target->type, $target->id, $this->weekStartFromIntent($intent)));

        return $this->formatter->handle($schedule);
    }

    /** @param list<string> $parts */
    private function subscribe(int $chatId, array $parts): string
    {
        $target = $this->targetFromParts($parts);
        $this->ensureSubscriptionTarget($target);
        $created = $this->subscriptions->handle($chatId, $target);

        if (!$created) {
            return sprintf('Підписка на %s вже існує.', $target->label);
        }

        return sprintf('Підписку на %s створено.', $target->label);
    }

    private function subscribeFromIntent(int $chatId, TelegramIntent $intent): string
    {
        $target = $this->targetFromIntent($intent, allowRoom: false);
        $created = $this->subscriptions->handle($chatId, $target);

        if (!$created) {
            return sprintf('Підписка на %s вже існує.', $target->label);
        }

        return sprintf('Підписку на %s створено.', $target->label);
    }

    /** @param list<string> $parts */
    private function unsubscribe(int $chatId, array $parts): string
    {
        $target = $this->targetFromParts($parts);
        $this->ensureSubscriptionTarget($target);
        $deleted = $this->unsubscriptions->handle($chatId, $target);

        if (!$deleted) {
            return sprintf('Підписку на %s не знайдено.', $target->label);
        }

        return sprintf('Підписку на %s видалено.', $target->label);
    }

    private function unsubscribeFromIntent(int $chatId, TelegramIntent $intent): string
    {
        $target = $this->targetFromIntent($intent, allowRoom: false);
        $deleted = $this->unsubscriptions->handle($chatId, $target);

        if (!$deleted) {
            return sprintf('Підписку на %s не знайдено.', $target->label);
        }

        return sprintf('Підписку на %s видалено.', $target->label);
    }

    /** @param list<string> $parts */
    private function targetFromParts(array $parts): TelegramTarget
    {
        if (count($parts) < 3) {
            throw ApiException::validation(['command' => 'Вкажіть тип і назву: group КН-22 або teacher Імʼя Прізвище.']);
        }

        return $this->targets->get($parts[1], $parts[2]);
    }

    private function targetFromIntent(TelegramIntent $intent, bool $allowRoom): TelegramTarget
    {
        if ($intent->targetType === null || $intent->targetName === null) {
            throw ApiException::validation(['target' => 'Вкажіть групу, викладача або аудиторію.']);
        }

        $target = $this->targets->get($intent->targetType, $intent->targetName);

        if (!$allowRoom) {
            $this->ensureSubscriptionTarget($target);
        }

        return $target;
    }

    private function ensureSubscriptionTarget(TelegramTarget $target): void
    {
        if ($target->type === 'room') {
            throw ApiException::validation(['type' => 'Підписки доступні лише для груп і викладачів.']);
        }
    }

    private function help(): string
    {
        return implode("\n", [
            'Доступні команди:',
            '/schedule group КН-22',
            '/schedule teacher Імʼя Прізвище',
            '/schedule room Lab 1',
            '/subscribe group КН-22',
            '/unsubscribe group КН-22',
        ]);
    }

    private function currentWeekStart(): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Kiev'));

        return $now->modify(sprintf('-%d days', ((int) $now->format('N')) - 1))->format('Y-m-d');
    }

    private function weekStartFromIntent(TelegramIntent $intent): string
    {
        if ($intent->weekStart !== null) {
            return $this->monday($intent->weekStart);
        }

        if ($intent->date !== null) {
            return $this->monday($intent->date);
        }

        if ($intent->range === 'tomorrow') {
            $tomorrow = new \DateTimeImmutable('tomorrow', new \DateTimeZone('Europe/Kiev'));

            return $tomorrow->modify(sprintf('-%d days', ((int) $tomorrow->format('N')) - 1))->format('Y-m-d');
        }

        return $this->currentWeekStart();
    }

    private function monday(string $date): string
    {
        $dateTime = new \DateTimeImmutable($date, new \DateTimeZone('Europe/Kiev'));

        return $dateTime->modify(sprintf('-%d days', ((int) $dateTime->format('N')) - 1))->format('Y-m-d');
    }

    private function validationMessage(ApiException $exception): string
    {
        $body = $exception->getBody();
        $errors = $body['errors'] ?? null;

        if (is_array($errors)) {
            $messages = array_filter($errors, 'is_string');

            return $messages === [] ? 'Запит не вдалося виконати.' : implode("\n", $messages);
        }

        return is_string($body['error'] ?? null) ? $body['error'] : 'Запит не вдалося виконати.';
    }

}
