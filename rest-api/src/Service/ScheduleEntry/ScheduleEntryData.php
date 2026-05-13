<?php

declare(strict_types=1);

namespace App\Service\ScheduleEntry;

use App\Entity\Group;
use App\Entity\Room;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\TeachingLoad;
use App\Entity\TimeSlot;
use App\Enum\LessonType;
use App\Enum\WeekParity;

final readonly class ScheduleEntryData
{
    /**
     * @param list<Group> $groups
     * @param list<TeachingLoad> $teachingLoads
     */
    public function __construct(
        public Subject $subject,
        public Teacher $teacher,
        public LessonType $lessonType,
        public Room $room,
        public TimeSlot $timeSlot,
        public int $dayOfWeek,
        public WeekParity $weekParity,
        public array $groups,
        public array $teachingLoads,
    ) {}
}
