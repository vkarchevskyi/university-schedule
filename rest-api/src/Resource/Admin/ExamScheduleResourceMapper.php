<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\ExamSchedule;
use App\Entity\ExamScheduleEntry;
use App\Enum\ExamScheduleStatus;

final readonly class ExamScheduleResourceMapper
{
    public function __construct(private ExamScheduleEntryResourceMapper $entries) {}

    public function map(ExamSchedule $schedule): ExamScheduleResource
    {
        $entries = [];

        foreach ($schedule->getEntries() as $entry) {
            if ($entry->getDeletedAt() === null) {
                $entries[] = $this->entries->map($entry);
            }
        }

        usort(
            $entries,
            static fn(ExamScheduleEntryResource $left, ExamScheduleEntryResource $right): int => [$left->entryDate, $left->startsAt, $left->id] <=> [$right->entryDate, $right->startsAt, $right->id],
        );

        return new ExamScheduleResource(
            $schedule->getId(),
            $schedule->getSemester()?->getId(),
            $this->status($schedule->getStatus()),
            $schedule->getCreatedBy()->getId(),
            $schedule->getCreatedAt()->format(\DateTimeInterface::ATOM),
            $schedule->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            $schedule->getDeletedAt()?->format(\DateTimeInterface::ATOM),
            $entries,
        );
    }

    private function status(ExamScheduleStatus $status): string
    {
        return match ($status) {
            ExamScheduleStatus::Draft => 'draft',
            ExamScheduleStatus::Published => 'published',
        };
    }
}
