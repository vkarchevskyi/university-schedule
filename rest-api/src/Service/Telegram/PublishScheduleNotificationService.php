<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Entity\Schedule;

final readonly class PublishScheduleNotificationService
{
    public function __construct(private TelegramNotificationPublisherInterface $publisher) {}

    public function handle(Schedule $schedule): void
    {
        $scheduleId = $schedule->getId();
        if ($scheduleId === null) {
            return;
        }

        foreach ($this->targets($schedule) as $target) {
            $this->publisher->publish([
                'eventType' => 'schedule_published',
                'scheduleId' => $scheduleId,
                'targetType' => $target['type'],
                'targetId' => $target['id'],
            ]);
        }
    }

    /**
     * @return list<array{type: string, id: int}>
     */
    private function targets(Schedule $schedule): array
    {
        $targets = [];

        foreach ($schedule->getEntries() as $entry) {
            $teacherId = $entry->getTeacher()->getId();
            if ($teacherId !== null) {
                $targets[sprintf('teacher:%d', $teacherId)] = ['type' => 'teacher', 'id' => $teacherId];
            }

            foreach ($entry->getGroups() as $entryGroup) {
                $groupId = $entryGroup->getGroup()->getId();
                if ($groupId !== null) {
                    $targets[sprintf('group:%d', $groupId)] = ['type' => 'group', 'id' => $groupId];
                }
            }
        }

        return array_values($targets);
    }
}
