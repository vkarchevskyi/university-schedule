<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\ExamScheduleEntry;
use App\Entity\ExamScheduleEntryGroup;
use App\Enum\ExamScheduleEntryType;

final readonly class ExamScheduleEntryResourceMapper
{
    public function map(ExamScheduleEntry $entry): ExamScheduleEntryResource
    {
        $groupIds = [];
        foreach ($entry->getGroups() as $group) {
            $groupIds[] = $this->groupId($group);
        }
        sort($groupIds);

        return new ExamScheduleEntryResource(
            $entry->getId(),
            $entry->getExamSchedule()?->getId(),
            $this->type($entry->getType()),
            $entry->getSubject()->getId(),
            $entry->getTeacher()->getId(),
            $entry->getRoom()->getId(),
            $entry->getEntryDate()->format('Y-m-d'),
            $entry->getStartsAt()->format('H:i:s'),
            $groupIds,
            $entry->getDeletedAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    private function type(ExamScheduleEntryType $type): string
    {
        return match ($type) {
            ExamScheduleEntryType::Consultation => 'consultation',
            ExamScheduleEntryType::Exam => 'exam',
        };
    }

    private function groupId(ExamScheduleEntryGroup $group): int
    {
        $id = $group->getGroup()->getId();

        if ($id === null) {
            throw new \LogicException('Exam schedule entry group must be persisted before mapping.');
        }

        return $id;
    }
}
