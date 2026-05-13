<?php

declare(strict_types=1);

namespace App\Service\ExamScheduleEntry;

use App\Entity\Group;
use App\Entity\Room;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Enum\ExamScheduleEntryType;

final readonly class ExamScheduleEntryData
{
    /**
     * @param list<Group> $groups
     */
    public function __construct(
        public ExamScheduleEntryType $type,
        public Subject $subject,
        public Teacher $teacher,
        public Room $room,
        public array $groups,
        public \DateTimeImmutable $entryDate,
        public \DateTimeImmutable $startsAt,
    ) {}
}
