<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\ScheduleEntry;
use App\Entity\ScheduleEntryGroup;
use App\Entity\ScheduleEntryTeachingLoad;

final readonly class ScheduleEntryResourceMapper
{
    public function map(ScheduleEntry $entry): ScheduleEntryResource
    {
        $groupIds = [];
        foreach ($entry->getGroups() as $group) {
            $groupIds[] = $this->groupId($group);
        }

        $teachingLoadIds = [];
        foreach ($entry->getTeachingLoads() as $teachingLoad) {
            $teachingLoadIds[] = $this->teachingLoadId($teachingLoad);
        }

        sort($groupIds);
        sort($teachingLoadIds);

        return new ScheduleEntryResource(
            $entry->getId(),
            $entry->getSchedule()?->getId(),
            $entry->getSubject()->getId(),
            $entry->getTeacher()->getId(),
            strtolower($entry->getLessonType()->name),
            $entry->getRoom()->getId(),
            $entry->getTimeSlot()->getId(),
            $entry->getDayOfWeek(),
            strtolower($entry->getWeekParity()->name),
            $groupIds,
            $teachingLoadIds,
            $entry->getSubgroup(),
        );
    }

    private function groupId(ScheduleEntryGroup $group): int
    {
        $id = $group->getGroup()->getId();

        if ($id === null) {
            throw new \LogicException('Schedule entry group must be persisted before mapping.');
        }

        return $id;
    }

    private function teachingLoadId(ScheduleEntryTeachingLoad $teachingLoad): int
    {
        $id = $teachingLoad->getTeachingLoad()->getId();

        if ($id === null) {
            throw new \LogicException('Teaching load must be persisted before mapping.');
        }

        return $id;
    }
}
