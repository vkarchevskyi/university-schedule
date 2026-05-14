<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Resource\Public\PublicScheduleResource;
use App\Resource\Public\ScheduleGroupResource;
use App\Resource\Public\ScheduleItemResource;

final readonly class FormatTelegramScheduleService
{
    public function handle(PublicScheduleResource $schedule): string
    {
        if ($schedule->items === []) {
            return 'На вибраний тиждень занять не знайдено.';
        }

        $lines = [sprintf('Розклад на тиждень з %s', $schedule->weekStart)];

        foreach ($schedule->items as $item) {
            $lines[] = $this->item($item);
        }

        return implode("\n", $lines);
    }

    private function item(ScheduleItemResource $item): string
    {
        $groups = array_map(static fn(ScheduleGroupResource $group): string => $group->name, $item->groups);
        $status = $item->isCancelled ? ' [скасовано]' : '';

        return sprintf(
            '%s %s-%s: %s, %s %s, ауд. %s, %s%s',
            $item->date,
            substr($item->timeSlot->startsAt, 0, 5),
            substr($item->timeSlot->endsAt, 0, 5),
            $item->subject->name,
            $item->teacher->firstName,
            $item->teacher->lastName,
            $item->room->name,
            implode(', ', $groups),
            $status,
        );
    }
}
