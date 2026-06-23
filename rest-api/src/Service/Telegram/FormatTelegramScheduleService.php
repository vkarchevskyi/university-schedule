<?php

declare(strict_types=1);

namespace App\Service\Telegram;

use App\Resource\Public\PublicScheduleResource;
use App\Resource\Public\ScheduleItemResource;

final readonly class FormatTelegramScheduleService
{
    public function handle(PublicScheduleResource $schedule): string
    {
        if ($schedule->items === []) {
            return 'На вибраний тиждень занять не знайдено.';
        }

        $blocks = [];

        foreach ($this->groupByDate($schedule->items) as $date => $items) {
            $blocks[] = $this->dayBlock($items[0]->dayOfWeek, $date, $items);
        }

        return implode("\n\n", $blocks);
    }

    /**
     * @param list<ScheduleItemResource> $items
     *
     * @return array<string, list<ScheduleItemResource>>
     */
    private function groupByDate(array $items): array
    {
        $grouped = [];

        foreach ($items as $item) {
            $grouped[$item->date][] = $item;
        }

        return $grouped;
    }

    /**
     * @param list<ScheduleItemResource> $items
     */
    private function dayBlock(int $dayOfWeek, string $date, array $items): string
    {
        $lines = [
            sprintf(
                '%s <b>%s (%s)</b>',
                $this->dayEmoji($dayOfWeek),
                $this->escape($this->dayNameUa($dayOfWeek)),
                $this->escape($this->displayDate($date)),
            ),
        ];

        foreach (array_values($items) as $index => $item) {
            $lines[] = $this->itemLine($item, $index + 1);
        }

        return implode("\n", $lines);
    }

    private function itemLine(ScheduleItemResource $item, int $number): string
    {
        $time = sprintf(
            '%s-%s',
            substr($item->timeSlot->startsAt, 0, 5),
            substr($item->timeSlot->endsAt, 0, 5),
        );

        $line = sprintf(
            '%s <b>%s</b> <b>%s</b> |%s| <i>(%s)</i>',
            $this->numberEmoji($number),
            $this->escape($time),
            $this->escape($item->subject->name),
            $this->escape($item->room->name),
            $this->escape($this->teacherShortName($item->teacher->firstName, $item->teacher->lastName)),
        );

        if ($item->isCancelled) {
            return $line . ' <i>(скасовано)</i>';
        }

        return $line;
    }

    private function dayNameUa(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            1 => 'Понеділок',
            2 => 'Вівторок',
            3 => 'Середа',
            4 => 'Четвер',
            5 => 'П\'ятниця',
            6 => 'Субота',
            7 => 'Неділя',
            default => 'День',
        };
    }

    private function dayEmoji(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            1 => '🌵',
            2 => '🌳',
            3 => '🌴',
            4 => '🌲',
            5 => '🎄',
            default => '📅',
        };
    }

    private function numberEmoji(int $number): string
    {
        $emojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣', '6️⃣', '7️⃣', '8️⃣', '9️⃣', '🔟'];

        return $emojis[$number - 1] ?? sprintf('%d.', $number);
    }

    private function displayDate(string $date): string
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if (!$parsed instanceof \DateTimeImmutable) {
            return $date;
        }

        return $parsed->format('d.m');
    }

    private function teacherShortName(string $firstName, string $lastName): string
    {
        $initial = mb_substr(trim($firstName), 0, 1);

        return sprintf('%s %s.', $lastName, $initial);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
