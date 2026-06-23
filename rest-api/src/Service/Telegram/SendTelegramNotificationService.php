<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Dto\PublicScheduleQueryDto;
use App\Service\PublicSchedule\GetPublicScheduleService;

final readonly class SendTelegramNotificationService
{
    public function __construct(
        private ListTelegramSubscriptionsService $subscriptions,
        private GetPublicScheduleService $publicSchedules,
        private FormatTelegramScheduleService $formatter,
        private TelegramSenderInterface $sender,
    ) {}

    /** @param array<string, mixed> $message */
    public function handle(array $message): void
    {
        $targetType = is_string($message['targetType'] ?? null) ? $message['targetType'] : '';
        $targetId = is_int($message['targetId'] ?? null) ? $message['targetId'] : 0;

        if (!in_array($targetType, ['group', 'teacher'], true) || $targetId < 1) {
            return;
        }

        $schedule = $this->publicSchedules->get(new PublicScheduleQueryDto($targetType, $targetId, $this->currentWeekStart()));
        $text = "<b>Опубліковано оновлений розклад.</b>\n\n" . $this->formatter->handle($schedule);

        foreach ($this->subscriptions->list($targetType, $targetId) as $subscription) {
            $this->sender->sendMessage($subscription->getTelegramChatId(), $text, [], 'HTML');
        }
    }

    private function currentWeekStart(): string
    {
        $now = new \DateTimeImmutable('now');

        return $now->modify(sprintf('-%d days', ((int) $now->format('N')) - 1))->format('Y-m-d');
    }
}
