<?php

declare(strict_types=1);

namespace App\Service\ScheduleEntry;

use App\Dto\Admin\ScheduleEntryRequestDto;
use App\Entity\Group;
use App\Entity\Room;
use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\TeachingLoad;
use App\Entity\TimeSlot;
use App\Enum\RoomType;
use App\Exception\ApiException;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;

final class ResolveScheduleEntryDataService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function resolve(Schedule $schedule, ScheduleEntryRequestDto $data, ?ScheduleEntry $currentEntry = null): ScheduleEntryData
    {
        $subject = $data->subjectId === null ? $this->currentSubject($currentEntry) : $this->getEntity(Subject::class, $this->positiveInt($data->subjectId));
        $teacher = $data->teacherId === null ? $this->currentTeacher($currentEntry) : $this->getEntity(Teacher::class, $this->positiveInt($data->teacherId));
        $lessonType = $data->lessonType === null ? $this->currentLessonType($currentEntry) : $this->lessonType($data->lessonType);
        $room = $data->roomId === null ? $this->currentRoom($currentEntry) : $this->getEntity(Room::class, $this->positiveInt($data->roomId));
        $timeSlot = $data->timeSlotId === null ? $this->currentTimeSlot($currentEntry) : $this->getEntity(TimeSlot::class, $this->positiveInt($data->timeSlotId));
        $dayOfWeek = $data->dayOfWeek === null ? $this->currentDayOfWeek($currentEntry) : $this->dayOfWeek($data->dayOfWeek);
        $weekParity = $data->weekParity === null ? $this->currentWeekParity($currentEntry) : $this->weekParity($data->weekParity);
        $groups = $data->groupIds === null ? $this->currentGroups($currentEntry) : $this->groups($data->groupIds);
        $teachingLoads = $data->teachingLoadIds === null ? $this->currentTeachingLoads($currentEntry) : $this->teachingLoads($data->teachingLoadIds);

        $resolved = new ScheduleEntryData($subject, $teacher, $lessonType, $room, $timeSlot, $dayOfWeek, $weekParity, $groups, $teachingLoads);
        $this->validateTeachingLoads($schedule, $resolved);

        return $resolved;
    }

    private function currentSubject(?ScheduleEntry $entry): Subject
    {
        return $this->requireCurrentEntry($entry)->getSubject();
    }

    private function currentTeacher(?ScheduleEntry $entry): Teacher
    {
        return $this->requireCurrentEntry($entry)->getTeacher();
    }

    private function currentLessonType(?ScheduleEntry $entry): \App\Enum\LessonType
    {
        return $this->requireCurrentEntry($entry)->getLessonType();
    }

    private function currentRoom(?ScheduleEntry $entry): Room
    {
        return $this->requireCurrentEntry($entry)->getRoom();
    }

    private function currentTimeSlot(?ScheduleEntry $entry): TimeSlot
    {
        return $this->requireCurrentEntry($entry)->getTimeSlot();
    }

    private function currentDayOfWeek(?ScheduleEntry $entry): int
    {
        return $this->requireCurrentEntry($entry)->getDayOfWeek();
    }

    private function currentWeekParity(?ScheduleEntry $entry): \App\Enum\WeekParity
    {
        return $this->requireCurrentEntry($entry)->getWeekParity();
    }

    /** @return list<Group> */
    private function currentGroups(?ScheduleEntry $entry): array
    {
        $groups = [];

        foreach ($this->requireCurrentEntry($entry)->getGroups() as $entryGroup) {
            $groups[] = $entryGroup->getGroup();
        }

        return $groups;
    }

    /** @return list<TeachingLoad> */
    private function currentTeachingLoads(?ScheduleEntry $entry): array
    {
        $teachingLoads = [];

        foreach ($this->requireCurrentEntry($entry)->getTeachingLoads() as $entryTeachingLoad) {
            $teachingLoads[] = $entryTeachingLoad->getTeachingLoad();
        }

        return $teachingLoads;
    }

    private function requireCurrentEntry(?ScheduleEntry $entry): ScheduleEntry
    {
        if (!$entry instanceof ScheduleEntry) {
            throw ApiException::validation(['entry' => 'Expected schedule entry data.']);
        }

        return $entry;
    }

    /**
     * @param list<int> $ids
     *
     * @return list<Group>
     */
    private function groups(array $ids): array
    {
        $groups = [];

        foreach ($this->uniquePositiveIds($ids, 'groupIds') as $id) {
            $groups[] = $this->getEntity(Group::class, $id);
        }

        return $groups;
    }

    /**
     * @param list<int> $ids
     *
     * @return list<TeachingLoad>
     */
    private function teachingLoads(array $ids): array
    {
        $teachingLoads = [];

        foreach ($this->uniquePositiveIds($ids, 'teachingLoadIds') as $id) {
            $teachingLoads[] = $this->getEntity(TeachingLoad::class, $id);
        }

        return $teachingLoads;
    }

    /**
     * @param list<int> $ids
     *
     * @return list<int>
     */
    private function uniquePositiveIds(array $ids, string $field): array
    {
        $uniqueIds = [];

        foreach ($ids as $id) {
            $id = $this->positiveInt($id);

            if (in_array($id, $uniqueIds, true)) {
                throw ApiException::validation([$field => 'Expected unique identifiers.']);
            }

            $uniqueIds[] = $id;
        }

        return $uniqueIds;
    }

    private function validateTeachingLoads(Schedule $schedule, ScheduleEntryData $data): void
    {
        $semester = $schedule->getSemester();

        if ($semester === null) {
            throw ApiException::validation(['schedule' => 'Schedule must belong to a semester.']);
        }

        $groupIds = $this->entityIds($data->groups);

        foreach ($data->teachingLoads as $teachingLoad) {
            if ($teachingLoad->getDeletedAt() !== null) {
                throw ApiException::validation(['teachingLoadIds' => 'Deleted teaching loads cannot be scheduled.']);
            }

            if ($teachingLoad->getSemester() !== $semester) {
                throw ApiException::validation(['teachingLoadIds' => 'Teaching load must belong to the schedule semester.']);
            }

            if ($teachingLoad->getSubject() !== $data->subject || $teachingLoad->getTeacher() !== $data->teacher || $teachingLoad->getLessonType() !== $data->lessonType) {
                throw ApiException::validation(['teachingLoadIds' => 'Teaching load must match subject, teacher, and lesson type.']);
            }

            if ($teachingLoad->requiresComputerRoom() && $data->room->getType() !== RoomType::Computer) {
                throw ApiException::validation(['roomId' => 'Teaching load requires a computer room.']);
            }

            $teachingLoadGroupId = $teachingLoad->getGroup()->getId();

            if ($teachingLoadGroupId === null || !in_array($teachingLoadGroupId, $groupIds, true)) {
                throw ApiException::validation(['groupIds' => 'Schedule entry groups must include each teaching load group.']);
            }
        }
    }

    /**
     * @param list<object> $entities
     *
     * @return list<int>
     */
    private function entityIds(array $entities): array
    {
        $ids = [];

        foreach ($entities as $entity) {
            if (!method_exists($entity, 'getId')) {
                throw new \LogicException('Expected entity to expose getId().');
            }

            $id = $entity->getId();

            if (!is_int($id)) {
                throw new \LogicException('Expected persisted entity identifier.');
            }

            $ids[] = $id;
        }

        return $ids;
    }
}
