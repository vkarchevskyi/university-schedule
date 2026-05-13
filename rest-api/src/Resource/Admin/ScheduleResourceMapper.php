<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\Schedule;

final readonly class ScheduleResourceMapper
{
    public function __construct(private ScheduleEntryResourceMapper $entries) {}

    public function map(Schedule $schedule): ScheduleResource
    {
        $entries = [];

        foreach ($schedule->getEntries() as $entry) {
            $entries[] = $this->entries->map($entry);
        }

        usort(
            $entries,
            static fn(ScheduleEntryResource $left, ScheduleEntryResource $right): int => [$left->dayOfWeek, $left->timeSlotId, $left->id] <=> [$right->dayOfWeek, $right->timeSlotId, $right->id],
        );

        return new ScheduleResource(
            $schedule->getId(),
            $schedule->getSemester()?->getId(),
            $schedule->getStatus()->value,
            $schedule->getValidFrom()->format('Y-m-d'),
            $schedule->getValidTo()->format('Y-m-d'),
            $schedule->getCreatedBy()->getId(),
            $schedule->getCreatedAt()->format(\DateTimeInterface::ATOM),
            $schedule->getPublishedAt()?->format(\DateTimeInterface::ATOM),
            $entries,
        );
    }
}
