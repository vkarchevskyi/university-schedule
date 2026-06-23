<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Dto\PublicScheduleQueryDto;
use App\Exception\ApiException;
use App\Resource\Public\PublicScheduleResource;
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
        private ListTelegramTargetsService $targetLists,
        private ListChatTelegramSubscriptionsService $chatSubscriptions,
        private TelegramFlowStateService $flowState,
    ) {}

    /** @param array<string, mixed> $payload */
    public function handle(array $payload): void
    {
        $callbackQuery = $payload['callback_query'] ?? null;
        if (is_array($callbackQuery)) {
            $this->handleCallbackQuery($callbackQuery);

            return;
        }

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
            $response = new TelegramMessage($this->validationMessage($exception));
        }

        $this->sender->sendMessage($chatId, $response->text, $response->keyboard, $response->parseMode);
    }

    /** @param array<mixed, mixed> $callbackQuery */
    private function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackQueryId = is_string($callbackQuery['id'] ?? null) ? $callbackQuery['id'] : null;
        $data = is_string($callbackQuery['data'] ?? null) ? $callbackQuery['data'] : null;
        $message = is_array($callbackQuery['message'] ?? null) ? $callbackQuery['message'] : null;
        $chat = is_array($message) && is_array($message['chat'] ?? null) ? $message['chat'] : null;
        $chatId = is_array($chat) && is_int($chat['id'] ?? null) ? $chat['id'] : null;

        if ($callbackQueryId === null || $data === null || $chatId === null) {
            return;
        }

        try {
            $response = $this->callbackResponse($chatId, $data);
            $this->sender->answerCallbackQuery($callbackQueryId);
        } catch (ApiException $exception) {
            $response = new TelegramMessage($this->validationMessage($exception));
            $this->sender->answerCallbackQuery($callbackQueryId, 'Запит не вдалося виконати.');
        }

        $this->sender->sendMessage($chatId, $response->text, $response->keyboard, $response->parseMode);
    }

    private function response(int $chatId, string $text): TelegramMessage
    {
        if (!str_starts_with($text, '/')) {
            return $this->freeText($chatId, $text);
        }

        $parts = preg_split('/\s+/', $text, 3) ?: [];
        $command = strtolower($parts[0] ?? '');

        return match ($command) {
            '/start', '/help' => new TelegramMessage($this->help()),
            '/schedule' => $this->schedule($chatId, $parts),
            '/subscribe' => $this->subscribe($chatId, $parts),
            '/unsubscribe' => $this->unsubscribe($chatId, $parts),
            default => new TelegramMessage('Команду не розпізнано. Надішліть /start, щоб побачити доступні команди.'),
        };
    }

    private function freeText(int $chatId, string $text): TelegramMessage
    {
        try {
            $intent = $this->intentParser->handle($text);
        } catch (AiParserUnavailableException) {
            return new TelegramMessage('AI-помічник тимчасово недоступний. Спробуйте пізніше або скористайтеся командою /schedule.');
        }

        if ($intent->confidence < self::AI_CONFIDENCE_THRESHOLD || $intent->intent === 'unknown') {
            return new TelegramMessage($intent->clarificationQuestion ?? 'Уточніть, будь ласка, групу, викладача або аудиторію.');
        }

        return match ($intent->intent) {
            'get_schedule' => $this->scheduleFromIntent($intent),
            'subscribe' => new TelegramMessage($this->subscribeFromIntent($chatId, $intent)),
            'unsubscribe' => new TelegramMessage($this->unsubscribeFromIntent($chatId, $intent)),
            'help' => new TelegramMessage($this->help()),
            default => new TelegramMessage($intent->clarificationQuestion ?? 'Уточніть, будь ласка, що саме потрібно зробити.'),
        };
    }

    private function callbackResponse(int $chatId, string $data): TelegramMessage
    {
        if ($data === 'tg:cancel') {
            $this->flowState->clear($chatId);

            return new TelegramMessage('Дію скасовано.');
        }

        $parts = explode(':', $data);
        if (count($parts) < 2 || $parts[0] !== 'tg') {
            throw ApiException::validation(['callback' => 'Кнопка застаріла або некоректна.']);
        }

        return match ($parts[1]) {
            'type' => $this->callbackType($chatId, $parts),
            'pick' => $this->callbackPick($chatId, $parts),
            'week' => $this->callbackWeek($parts),
            default => throw ApiException::validation(['callback' => 'Кнопка застаріла або некоректна.']),
        };
    }

    /** @param list<string> $parts */
    private function callbackType(int $chatId, array $parts): TelegramMessage
    {
        if (count($parts) !== 5 || !$this->validAction($parts[2]) || !$this->validType($parts[3], $parts[2]) || !ctype_digit($parts[4])) {
            throw ApiException::validation(['callback' => 'Кнопка застаріла або некоректна.']);
        }

        $action = $parts[2];
        $type = $parts[3];
        $page = (int) $parts[4];
        $state = $this->flowState->get($chatId);
        if ($state === null || $state->action !== $action) {
            throw ApiException::validation(['callback' => 'Кнопка застаріла. Почніть команду ще раз.']);
        }

        $this->flowState->save($chatId, new TelegramFlowState($action, $type, $page));

        return $this->entitySelectionMessage($action, $type, $page);
    }

    /** @param list<string> $parts */
    private function callbackPick(int $chatId, array $parts): TelegramMessage
    {
        if (count($parts) !== 5 || !$this->validAction($parts[2]) || !$this->validType($parts[3], $parts[2]) || !ctype_digit($parts[4])) {
            throw ApiException::validation(['callback' => 'Кнопка застаріла або некоректна.']);
        }

        $state = $this->flowState->get($chatId);
        if ($state === null || $state->action !== $parts[2] || ($state->targetType !== null && $state->targetType !== $parts[3])) {
            throw ApiException::validation(['callback' => 'Кнопка застаріла. Почніть команду ще раз.']);
        }

        $target = $this->targets->getById($parts[3], (int) $parts[4]);
        if ($target === null) {
            throw ApiException::validation(['target' => 'Обʼєкт не знайдено.']);
        }

        $this->flowState->clear($chatId);

        return match ($parts[2]) {
            'schedule' => $this->scheduleForTarget($target, $this->currentWeekStart()),
            'subscribe' => new TelegramMessage($this->subscribeTarget($chatId, $target)),
            'unsubscribe' => new TelegramMessage($this->unsubscribeTarget($chatId, $target)),
            default => throw ApiException::validation(['callback' => 'Кнопка застаріла або некоректна.']),
        };
    }

    /** @param list<string> $parts */
    private function callbackWeek(array $parts): TelegramMessage
    {
        if (count($parts) !== 6 || !$this->validType($parts[2], 'schedule') || !ctype_digit($parts[3]) || !$this->validDate($parts[4]) || !in_array($parts[5], ['-1', '0', '1'], true)) {
            throw ApiException::validation(['callback' => 'Кнопка застаріла або некоректна.']);
        }

        $target = $this->targets->getById($parts[2], (int) $parts[3]);
        if ($target === null) {
            throw ApiException::validation(['target' => 'Обʼєкт не знайдено.']);
        }

        $weekStart = $this->addWeeks($parts[4], (int) $parts[5]);

        return $this->scheduleForTarget($target, $weekStart);
    }

    /** @param list<string> $parts */
    private function schedule(int $chatId, array $parts): TelegramMessage
    {
        if (count($parts) < 3) {
            $this->flowState->save($chatId, new TelegramFlowState('schedule', null, 0));

            return $this->typeSelectionMessage('schedule');
        }

        $target = $this->targetFromParts($parts);

        return $this->scheduleForTarget($target, $this->currentWeekStart());
    }

    private function scheduleFromIntent(TelegramIntent $intent): TelegramMessage
    {
        $target = $this->targetFromIntent($intent, allowRoom: true);
        $schedule = $this->publicSchedules->get(new PublicScheduleQueryDto($target->type, $target->id, $this->weekStartFromIntent($intent)));

        return $this->scheduleMessage($schedule, $target, $this->weekStartFromIntent($intent));
    }

    /** @param list<string> $parts */
    private function subscribe(int $chatId, array $parts): TelegramMessage
    {
        if (count($parts) < 3) {
            $this->flowState->save($chatId, new TelegramFlowState('subscribe', null, 0));

            return $this->typeSelectionMessage('subscribe');
        }

        $target = $this->targetFromParts($parts);
        $this->ensureSubscriptionTarget($target);

        return new TelegramMessage($this->subscribeTarget($chatId, $target));
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
    private function unsubscribe(int $chatId, array $parts): TelegramMessage
    {
        if (count($parts) < 3) {
            $this->flowState->save($chatId, new TelegramFlowState('unsubscribe', null, 0));

            return $this->subscriptionSelectionMessage($chatId);
        }

        $target = $this->targetFromParts($parts);
        $this->ensureSubscriptionTarget($target);

        return new TelegramMessage($this->unsubscribeTarget($chatId, $target));
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

    private function typeSelectionMessage(string $action): TelegramMessage
    {
        $rows = [
            [new TelegramInlineButton('Група', sprintf('tg:type:%s:group:0', $action))],
            [new TelegramInlineButton('Викладач', sprintf('tg:type:%s:teacher:0', $action))],
        ];

        if ($action === 'schedule') {
            $rows[] = [new TelegramInlineButton('Аудиторія', 'tg:type:schedule:room:0')];
        }

        $rows[] = [new TelegramInlineButton('Скасувати', 'tg:cancel')];

        return new TelegramMessage(match ($action) {
            'schedule' => 'Оберіть, чий розклад показати:',
            'subscribe' => 'Оберіть тип підписки:',
            default => 'Оберіть тип:',
        }, $rows);
    }

    private function entitySelectionMessage(string $action, string $type, int $page): TelegramMessage
    {
        $targets = $this->targetLists->list($type, $page);
        if ($targets->items === []) {
            return new TelegramMessage('Нічого не знайдено.', [[new TelegramInlineButton('Назад', sprintf('tg:type:%s:%s:0', $action, $type))], [new TelegramInlineButton('Скасувати', 'tg:cancel')]]);
        }

        $keyboard = array_map(
            fn(TelegramTargetListItem $item): array => [new TelegramInlineButton($item->label, sprintf('tg:pick:%s:%s:%d', $action, $item->type, $item->id))],
            $targets->items,
        );

        $navigation = [];
        if ($targets->hasPrevious) {
            $navigation[] = new TelegramInlineButton('Назад', sprintf('tg:type:%s:%s:%d', $action, $type, $targets->page - 1));
        }
        if ($targets->hasNext) {
            $navigation[] = new TelegramInlineButton('Далі', sprintf('tg:type:%s:%s:%d', $action, $type, $targets->page + 1));
        }
        if ($navigation !== []) {
            $keyboard[] = $navigation;
        }
        $keyboard[] = [new TelegramInlineButton('Скасувати', 'tg:cancel')];

        return new TelegramMessage(sprintf('Оберіть %s:', $this->typeLabel($type)), $keyboard);
    }

    private function subscriptionSelectionMessage(int $chatId): TelegramMessage
    {
        $subscriptions = $this->chatSubscriptions->list($chatId);
        if ($subscriptions === []) {
            return new TelegramMessage('У вас немає активних підписок.');
        }

        $keyboard = array_map(
            fn(TelegramTargetListItem $item): array => [new TelegramInlineButton($item->label, sprintf('tg:pick:unsubscribe:%s:%d', $item->type, $item->id))],
            $subscriptions,
        );
        $keyboard[] = [new TelegramInlineButton('Скасувати', 'tg:cancel')];

        return new TelegramMessage('Оберіть підписку для видалення:', $keyboard);
    }

    private function scheduleForTarget(TelegramTarget $target, string $weekStart): TelegramMessage
    {
        $schedule = $this->publicSchedules->get(new PublicScheduleQueryDto($target->type, $target->id, $weekStart));

        return $this->scheduleMessage($schedule, $target, $weekStart);
    }

    private function scheduleMessage(PublicScheduleResource $schedule, TelegramTarget $target, string $weekStart): TelegramMessage
    {
        return new TelegramMessage(
            $this->formatter->handle($schedule),
            $this->weekNavigationKeyboard($target, $weekStart),
            'HTML',
        );
    }

    /** @return list<list<TelegramInlineButton>> */
    private function weekNavigationKeyboard(TelegramTarget $target, string $weekStart): array
    {
        return [[
            new TelegramInlineButton('Попередній', sprintf('tg:week:%s:%d:%s:-1', $target->type, $target->id, $weekStart)),
            new TelegramInlineButton('Поточний', sprintf('tg:week:%s:%d:%s:0', $target->type, $target->id, $this->currentWeekStart())),
            new TelegramInlineButton('Наступний', sprintf('tg:week:%s:%d:%s:1', $target->type, $target->id, $weekStart)),
        ]];
    }

    private function subscribeTarget(int $chatId, TelegramTarget $target): string
    {
        $this->ensureSubscriptionTarget($target);
        $created = $this->subscriptions->handle($chatId, $target);

        if (!$created) {
            return sprintf('Підписка на %s вже існує.', $target->label);
        }

        return sprintf('Підписку на %s створено.', $target->label);
    }

    private function unsubscribeTarget(int $chatId, TelegramTarget $target): string
    {
        $this->ensureSubscriptionTarget($target);
        $deleted = $this->unsubscriptions->handle($chatId, $target);

        if (!$deleted) {
            return sprintf('Підписку на %s не знайдено.', $target->label);
        }

        return sprintf('Підписку на %s видалено.', $target->label);
    }

    private function validAction(string $action): bool
    {
        return in_array($action, ['schedule', 'subscribe', 'unsubscribe'], true);
    }

    private function validType(string $type, string $action): bool
    {
        return match ($action) {
            'schedule' => in_array($type, ['group', 'teacher', 'room'], true),
            'subscribe', 'unsubscribe' => in_array($type, ['group', 'teacher'], true),
            default => false,
        };
    }

    private function validDate(string $date): bool
    {
        $dateTime = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $dateTime instanceof \DateTimeImmutable && $dateTime->format('Y-m-d') === $date;
    }

    private function addWeeks(string $weekStart, int $weeks): string
    {
        return (new \DateTimeImmutable($weekStart, new \DateTimeZone('Europe/Kiev')))
            ->modify(sprintf('%+d weeks', $weeks))
            ->format('Y-m-d');
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'group' => 'групу',
            'teacher' => 'викладача',
            'room' => 'аудиторію',
            default => 'обʼєкт',
        };
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
