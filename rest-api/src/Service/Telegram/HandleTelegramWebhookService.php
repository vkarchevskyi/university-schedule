<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Dto\PublicScheduleQueryDto;
use App\Exception\ApiException;
use App\Service\PublicSchedule\GetPublicScheduleService;

final readonly class HandleTelegramWebhookService
{
    public function __construct(
        private TelegramSenderInterface $sender,
        private ResolveTelegramTargetService $targets,
        private CreateTelegramSubscriptionService $subscriptions,
        private DeleteTelegramSubscriptionService $unsubscriptions,
        private GetPublicScheduleService $publicSchedules,
        private FormatTelegramScheduleService $formatter,
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

    /** @param list<string> $parts */
    private function schedule(array $parts): string
    {
        $target = $this->targetFromParts($parts);
        $weekStart = $this->currentWeekStart();
        $schedule = $this->publicSchedules->get(new PublicScheduleQueryDto($target->type, $target->id, $weekStart));

        return $this->formatter->handle($schedule);
    }

    /** @param list<string> $parts */
    private function subscribe(int $chatId, array $parts): string
    {
        $target = $this->targetFromParts($parts);
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

    private function help(): string
    {
        return implode("\n", [
            'Доступні команди:',
            '/schedule group КН-22',
            '/schedule teacher Імʼя Прізвище',
            '/subscribe group КН-22',
            '/unsubscribe group КН-22',
        ]);
    }

    private function currentWeekStart(): string
    {
        $now = new \DateTimeImmutable('now');

        return $now->modify(sprintf('-%d days', ((int) $now->format('N')) - 1))->format('Y-m-d');
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
