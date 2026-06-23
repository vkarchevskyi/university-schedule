<?php

declare(strict_types=1);

namespace App\Service\PublicSchedule;

use App\Dto\PublicScheduleQueryDto;
use App\Entity\Group as StudentGroup;
use App\Entity\Lesson;
use App\Entity\LessonGroup;
use App\Entity\Room;
use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use App\Entity\ScheduleEntryGroup;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\TimeSlot;
use App\Enum\WeekParity;
use App\Exception\ApiException;
use App\Repository\ScheduleEntryRepository;
use App\Repository\ScheduleRepository;
use App\Resource\Public\PublicScheduleResource;
use App\Resource\Public\ScheduleGroupResource;
use App\Resource\Public\ScheduleItemResource;
use App\Resource\Public\ScheduleRoomResource;
use App\Resource\Public\ScheduleTeacherResource;
use App\Resource\Public\SubjectResource;
use App\Resource\Public\TimeSlotResource;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class GetPublicScheduleService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScheduleRepository $schedules,
        private ScheduleEntryRepository $entries,
    ) {}

    public function get(PublicScheduleQueryDto $query): PublicScheduleResource
    {
        $this->ensureFilterTargetExists($query);

        $weekStart = $query->weekStartDate();
        $weekEnd = $weekStart->modify('+6 days');
        $schedule = $this->schedules->findPublishedForWeek($weekStart, $weekEnd);

        if (!$schedule instanceof Schedule) {
            return $this->emptySchedule($query);
        }

        $items = [];

        foreach ($this->entries->findPublicEntriesForFilter($schedule, $query->type ?? '', $query->id ?? 0) as $entry) {
            $date = $weekStart->modify(sprintf('+%d days', $entry->getDayOfWeek() - 1));

            if ($date < $schedule->getValidFrom() || $date > $schedule->getValidTo()) {
                continue;
            }

            if (!$this->entryMatchesWeekParity($schedule, $entry, $weekStart)) {
                continue;
            }

            $items[] = $this->entryItem($entry, $date);
        }

        usort($items, fn(ScheduleItemResource $left, ScheduleItemResource $right): int => [
            $left->dayOfWeek,
            $left->timeSlot->number,
            $left->id,
        ] <=> [
            $right->dayOfWeek,
            $right->timeSlot->number,
            $right->id,
        ]);

        return new PublicScheduleResource($weekStart->format('Y-m-d'), $query->type ?? '', $query->id ?? 0, $items);
    }

    private function ensureFilterTargetExists(PublicScheduleQueryDto $query): void
    {
        $class = match ($query->type) {
            'group' => StudentGroup::class,
            'teacher' => Teacher::class,
            'room' => Room::class,
            default => null,
        };

        if ($class === null || !$this->entityManager->find($class, $query->id)) {
            throw ApiException::http(['error' => 'Filter target not found.'], Response::HTTP_NOT_FOUND);
        }
    }

    private function emptySchedule(PublicScheduleQueryDto $query): PublicScheduleResource
    {
        return new PublicScheduleResource($query->weekStartDate()->format('Y-m-d'), $query->type ?? '', $query->id ?? 0, []);
    }

    private function entryMatchesWeekParity(Schedule $schedule, ScheduleEntry $entry, \DateTimeImmutable $weekStart): bool
    {
        if ($entry->getWeekParity() === WeekParity::Both) {
            return true;
        }

        $semester = $schedule->getSemester();

        if ($semester === null) {
            return false;
        }

        $semesterWeekStart = $this->mondayOfWeek($semester->getStartsAt());
        $weeks = (int) floor(((int) $semesterWeekStart->diff($weekStart)->format('%r%a')) / 7);
        $firstWeekParity = $semester->getFirstWeekParity();
        $currentParity = $weeks % 2 === 0 ? $firstWeekParity : $this->oppositeParity($firstWeekParity);

        return $entry->getWeekParity() === $currentParity;
    }

    private function oppositeParity(WeekParity $weekParity): WeekParity
    {
        return match ($weekParity) {
            WeekParity::Odd => WeekParity::Even,
            WeekParity::Even => WeekParity::Odd,
            WeekParity::Both => WeekParity::Both,
        };
    }

    private function mondayOfWeek(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->modify(sprintf('-%d days', ((int) $date->format('N')) - 1));
    }

    private function entryItem(ScheduleEntry $entry, \DateTimeImmutable $date): ScheduleItemResource
    {
        $lesson = $this->lessonForDate($entry, $date);
        $source = $lesson?->isOverride() === true ? $lesson : $entry;
        $groups = $lesson instanceof Lesson && ($lesson->isOverride() || $lesson->isCancelled()) && $lesson->getGroups()->count() > 0
            ? $this->lessonGroups($lesson->getGroups())
            : $this->entryGroups($entry->getGroups());

        return new ScheduleItemResource(
            $entry->getId(),
            $date->format('Y-m-d'),
            $entry->getDayOfWeek(),
            strtolower($source->getLessonType()->name),
            $this->timeSlot($source->getTimeSlot()),
            $this->subject($source->getSubject()),
            $this->teacher($source->getTeacher()),
            $this->room($source->getRoom()),
            $groups,
            $lesson?->isCancelled() ?? false,
            $lesson?->isOverride() ?? false,
            $entry->getSubgroup(),
        );
    }

    private function lessonForDate(ScheduleEntry $entry, \DateTimeImmutable $date): ?Lesson
    {
        foreach ($entry->getLessons() as $lesson) {
            if ($lesson->getLessonDate()->format('Y-m-d') === $date->format('Y-m-d')) {
                return $lesson;
            }
        }

        return null;
    }

    /**
     * @param Collection<int, ScheduleEntryGroup> $groups
     *
     * @return list<ScheduleGroupResource>
     */
    private function entryGroups(Collection $groups): array
    {
        $resources = [];

        foreach ($groups as $group) {
            $resources[] = $this->group($group->getGroup());
        }

        return $resources;
    }

    /**
     * @param Collection<int, LessonGroup> $groups
     *
     * @return list<ScheduleGroupResource>
     */
    private function lessonGroups(Collection $groups): array
    {
        $resources = [];

        foreach ($groups as $group) {
            $resources[] = $this->group($group->getGroup());
        }

        return $resources;
    }

    private function group(StudentGroup $group): ScheduleGroupResource
    {
        return new ScheduleGroupResource($group->getId(), $group->getName());
    }

    private function subject(Subject $subject): SubjectResource
    {
        return new SubjectResource($subject->getId(), $subject->getName());
    }

    private function teacher(Teacher $teacher): ScheduleTeacherResource
    {
        return new ScheduleTeacherResource($teacher->getId(), $teacher->getFirstName(), $teacher->getLastName());
    }

    private function room(Room $room): ScheduleRoomResource
    {
        return new ScheduleRoomResource($room->getId(), $room->getName(), $room->getType()->value);
    }

    private function timeSlot(TimeSlot $timeSlot): TimeSlotResource
    {
        return new TimeSlotResource(
            $timeSlot->getId(),
            $timeSlot->getNumber(),
            $timeSlot->getStartsAt()->format('H:i'),
            $timeSlot->getEndsAt()->format('H:i'),
        );
    }
}
