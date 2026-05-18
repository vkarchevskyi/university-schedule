<?php

declare(strict_types=1);

namespace App\Service\ExamScheduleEntry;

use App\Dto\Admin\ExamScheduleEntryRequestDto;
use App\Entity\ExamScheduleEntry;
use App\Entity\Group;
use App\Entity\Room;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Enum\ExamScheduleEntryType;
use App\Exception\ApiException;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;

final class ResolveExamScheduleEntryDataService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function resolve(ExamScheduleEntryRequestDto $request, ?ExamScheduleEntry $entry = null): ExamScheduleEntryData
    {
        return new ExamScheduleEntryData(
            $this->resolveType($request, $entry),
            $this->resolveEntity(Subject::class, $request->subjectId, $entry?->getSubject()),
            $this->resolveEntity(Teacher::class, $request->teacherId, $entry?->getTeacher()),
            $this->resolveEntity(Room::class, $request->roomId, $entry?->getRoom()),
            $this->resolveGroups($request, $entry),
            $this->resolveDate($request, $entry),
            $this->resolveTime($request, $entry),
        );
    }

    private function resolveType(ExamScheduleEntryRequestDto $request, ?ExamScheduleEntry $entry): ExamScheduleEntryType
    {
        if ($request->type === null) {
            if ($entry instanceof ExamScheduleEntry) {
                return $entry->getType();
            }

            throw ApiException::validation(['type' => 'Expected exam schedule entry type.']);
        }

        if (is_string($request->type)) {
            return match (strtolower($request->type)) {
                'consultation' => ExamScheduleEntryType::Consultation,
                'exam' => ExamScheduleEntryType::Exam,
                default => throw ApiException::validation(['type' => 'Unknown exam schedule entry type.']),
            };
        }

        try {
            return ExamScheduleEntryType::from($request->type);
        } catch (\ValueError) {
            throw ApiException::validation(['type' => 'Unknown exam schedule entry type.']);
        }
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function resolveEntity(string $class, ?int $id, ?object $fallback): object
    {
        if ($id === null) {
            if ($fallback instanceof $class) {
                return $fallback;
            }

            throw ApiException::validation(['id' => 'Expected entity id.']);
        }

        return $this->getEntity($class, $this->positiveInt($id));
    }

    /** @return list<Group> */
    private function resolveGroups(ExamScheduleEntryRequestDto $request, ?ExamScheduleEntry $entry): array
    {
        if ($request->groupIds === null) {
            if (!$entry instanceof ExamScheduleEntry) {
                throw ApiException::validation(['groupIds' => 'Expected at least one group.']);
            }

            $groups = [];
            foreach ($entry->getGroups() as $group) {
                $groups[] = $group->getGroup();
            }

            return $groups;
        }

        $groups = [];
        foreach ($request->groupIds as $groupId) {
            $groups[] = $this->getEntity(Group::class, $this->positiveInt($groupId));
        }

        return $groups;
    }

    private function resolveDate(ExamScheduleEntryRequestDto $request, ?ExamScheduleEntry $entry): \DateTimeImmutable
    {
        if ($request->entryDate === null) {
            if ($entry instanceof ExamScheduleEntry) {
                return $entry->getEntryDate();
            }

            throw ApiException::validation(['entryDate' => 'Expected valid date.']);
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $request->entryDate);

        if (!$date instanceof \DateTimeImmutable) {
            throw ApiException::validation(['entryDate' => 'Expected valid date.']);
        }

        return $date;
    }

    private function resolveTime(ExamScheduleEntryRequestDto $request, ?ExamScheduleEntry $entry): \DateTimeImmutable
    {
        if ($request->startsAt === null) {
            if ($entry instanceof ExamScheduleEntry) {
                return $entry->getStartsAt();
            }

            throw ApiException::validation(['startsAt' => 'Expected valid time.']);
        }

        foreach (['!H:i:s', '!H:i'] as $format) {
            $time = \DateTimeImmutable::createFromFormat($format, $request->startsAt);

            if ($time instanceof \DateTimeImmutable) {
                return $time;
            }
        }

        throw ApiException::validation(['startsAt' => 'Expected valid time.']);
    }
}
