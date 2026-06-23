<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

use App\Entity\Group;
use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use App\Entity\TimeSlot;
use App\Enum\WeekParity;
use App\Service\ScheduleEntry\ScheduleEntryData;

final readonly class ScheduleEntryConflictPolicy
{
    public function conflict(Schedule $schedule, ScheduleEntryData $data, ?ScheduleEntry $ignoredEntry = null): ?ScheduleEntryConflict
    {
        foreach ($schedule->getEntries() as $entry) {
            if ($ignoredEntry instanceof ScheduleEntry && $entry === $ignoredEntry) {
                continue;
            }

            if ($entry->getDayOfWeek() !== $data->dayOfWeek || !$this->timeRangesOverlap($entry->getTimeSlot(), $data->timeSlot)) {
                continue;
            }

            if (!$this->weekParityOverlaps($entry->getWeekParity(), $data->weekParity)) {
                continue;
            }

            if ($entry->getTeacher() === $data->teacher) {
                return new ScheduleEntryConflict('teacherId', 'Teacher is already assigned at this time.');
            }

            if ($entry->getRoom() === $data->room) {
                return new ScheduleEntryConflict('roomId', 'Room is already assigned at this time.');
            }

            if ($this->subgroupsOverlap($entry->getSubgroup(), $data->subgroup) && $this->hasGroupOverlap($entry, $data->groups)) {
                return new ScheduleEntryConflict('groupIds', 'Group is already assigned at this time.');
            }
        }

        return null;
    }

    private function weekParityOverlaps(WeekParity $left, WeekParity $right): bool
    {
        return $left === WeekParity::Both || $right === WeekParity::Both || $left === $right;
    }

    private function subgroupsOverlap(?int $left, ?int $right): bool
    {
        return $left === null || $right === null || $left === $right;
    }

    private function timeRangesOverlap(TimeSlot $left, TimeSlot $right): bool
    {
        return $left->getStartsAt() < $right->getEndsAt() && $right->getStartsAt() < $left->getEndsAt();
    }

    /** @param list<Group> $groups */
    private function hasGroupOverlap(ScheduleEntry $entry, array $groups): bool
    {
        $groupIds = [];

        foreach ($groups as $group) {
            $id = $group->getId();

            if ($id !== null) {
                $groupIds[] = $id;
            }
        }

        foreach ($entry->getGroups() as $entryGroup) {
            $entryGroupId = $entryGroup->getGroup()->getId();

            if ($entryGroupId !== null && in_array($entryGroupId, $groupIds, true)) {
                return true;
            }
        }

        return false;
    }
}
