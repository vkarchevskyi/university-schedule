<?php

declare(strict_types=1);

namespace App\Resource\Public;

final readonly class ScheduleItemResource
{
    /**
     * @param list<ScheduleGroupResource> $groups
     */
    public function __construct(
        public ?int $id,
        public string $date,
        public int $dayOfWeek,
        public string $lessonType,
        public TimeSlotResource $timeSlot,
        public SubjectResource $subject,
        public ScheduleTeacherResource $teacher,
        public ScheduleRoomResource $room,
        public array $groups,
        public bool $isCancelled,
        public bool $isOverride,
    ) {}
}
