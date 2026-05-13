<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class ScheduleEntryResource
{
    /**
     * @param list<int> $groupIds
     * @param list<int> $teachingLoadIds
     */
    public function __construct(
        public ?int $id,
        public ?int $scheduleId,
        public ?int $subjectId,
        public ?int $teacherId,
        public string $lessonType,
        public ?int $roomId,
        public ?int $timeSlotId,
        public int $dayOfWeek,
        public string $weekParity,
        public array $groupIds,
        public array $teachingLoadIds,
    ) {}
}
