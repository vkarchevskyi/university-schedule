<?php

declare(strict_types=1);

namespace App\Service\ExamScheduleValidation;

use App\Entity\ExamSchedule;
use App\Entity\ExamScheduleEntry;
use App\Entity\Group;
use App\Entity\TeacherSubject;
use App\Enum\ExamScheduleEntryType;
use App\Resource\Admin\ExamScheduleConflictResource;
use App\Resource\Admin\ExamScheduleValidationResource;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ValidateExamScheduleRulesService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private int $consultationDaysBefore,
        private int $minimumDaysBetweenGroupExams,
    ) {}

    public function validate(ExamSchedule $schedule): ExamScheduleValidationResource
    {
        $entries = $this->activeEntries($schedule);
        $conflicts = [
            ...$this->entryConflicts($entries),
            ...$this->capacityConflicts($entries),
            ...$this->teacherSubjectConflicts($entries),
            ...$this->consultationConflicts($entries),
            ...$this->minimumIntervalConflicts($entries),
        ];

        return new ExamScheduleValidationResource($conflicts === [], $conflicts);
    }

    /** @return list<ExamScheduleEntry> */
    private function activeEntries(ExamSchedule $schedule): array
    {
        $entries = [];

        foreach ($schedule->getEntries() as $entry) {
            if ($entry->getDeletedAt() === null) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * @param list<ExamScheduleEntry> $entries
     *
     * @return list<ExamScheduleConflictResource>
     */
    private function entryConflicts(array $entries): array
    {
        $conflicts = [];

        foreach ($entries as $leftIndex => $left) {
            foreach (array_slice($entries, $leftIndex + 1) as $right) {
                if ($left->getEntryDate()->format('Y-m-d') !== $right->getEntryDate()->format('Y-m-d') || $left->getStartsAt()->format('H:i:s') !== $right->getStartsAt()->format('H:i:s')) {
                    continue;
                }

                $entryIds = $this->entryIds($left, $right);

                if ($left->getTeacher() === $right->getTeacher()) {
                    $conflicts[] = new ExamScheduleConflictResource('teacher_conflict', 'Teacher is already assigned at this exam time.', $entryIds);
                }

                if ($left->getRoom() === $right->getRoom()) {
                    $conflicts[] = new ExamScheduleConflictResource('room_conflict', 'Room is already assigned at this exam time.', $entryIds);
                }

                if ($this->hasGroupOverlap($left, $right)) {
                    $conflicts[] = new ExamScheduleConflictResource('group_conflict', 'Group is already assigned at this exam time.', $entryIds);
                }
            }
        }

        return $conflicts;
    }

    /**
     * @param list<ExamScheduleEntry> $entries
     *
     * @return list<ExamScheduleConflictResource>
     */
    private function capacityConflicts(array $entries): array
    {
        $conflicts = [];

        foreach ($entries as $entry) {
            $studentCount = 0;
            foreach ($entry->getGroups() as $group) {
                $studentCount += $group->getGroup()->getStudentCount();
            }

            if ($studentCount > $entry->getRoom()->getCapacity()) {
                $conflicts[] = new ExamScheduleConflictResource(
                    'room_capacity_conflict',
                    sprintf('Room capacity is %d, but exam groups contain %d students.', $entry->getRoom()->getCapacity(), $studentCount),
                    $this->entryIds($entry),
                );
            }
        }

        return $conflicts;
    }

    /**
     * @param list<ExamScheduleEntry> $entries
     *
     * @return list<ExamScheduleConflictResource>
     */
    private function teacherSubjectConflicts(array $entries): array
    {
        $conflicts = [];

        foreach ($entries as $entry) {
            $assignment = $this->entityManager->getRepository(TeacherSubject::class)->findOneBy([
                'teacher' => $entry->getTeacher(),
                'subject' => $entry->getSubject(),
            ]);

            if (!$assignment instanceof TeacherSubject) {
                $conflicts[] = new ExamScheduleConflictResource('teacher_subject_mismatch', 'Teacher is not assigned to this subject.', $this->entryIds($entry));
            }
        }

        return $conflicts;
    }

    /**
     * @param list<ExamScheduleEntry> $entries
     *
     * @return list<ExamScheduleConflictResource>
     */
    private function consultationConflicts(array $entries): array
    {
        $conflicts = [];

        foreach ($entries as $entry) {
            if ($entry->getType() !== ExamScheduleEntryType::Exam || $this->hasMatchingConsultation($entry, $entries)) {
                continue;
            }

            $conflicts[] = new ExamScheduleConflictResource(
                'consultation_missing',
                sprintf('Exam requires a matching consultation %d day(s) before the exam.', $this->consultationDaysBefore),
                $this->entryIds($entry),
            );
        }

        return $conflicts;
    }

    /**
     * @param list<ExamScheduleEntry> $entries
     */
    private function hasMatchingConsultation(ExamScheduleEntry $exam, array $entries): bool
    {
        $expectedDate = $exam->getEntryDate()->modify(sprintf('-%d days', $this->consultationDaysBefore))->format('Y-m-d');
        $examGroupIds = $this->groupIds($exam);

        foreach ($entries as $entry) {
            if ($entry->getType() !== ExamScheduleEntryType::Consultation) {
                continue;
            }

            if (
                $entry->getSubject() === $exam->getSubject()
                && $entry->getTeacher() === $exam->getTeacher()
                && $entry->getRoom() === $exam->getRoom()
                && $entry->getEntryDate()->format('Y-m-d') === $expectedDate
                && $this->groupIds($entry) === $examGroupIds
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<ExamScheduleEntry> $entries
     *
     * @return list<ExamScheduleConflictResource>
     */
    private function minimumIntervalConflicts(array $entries): array
    {
        $conflicts = [];

        foreach ($entries as $leftIndex => $left) {
            if ($left->getType() !== ExamScheduleEntryType::Exam) {
                continue;
            }

            foreach (array_slice($entries, $leftIndex + 1) as $right) {
                if ($right->getType() !== ExamScheduleEntryType::Exam || !$this->hasGroupOverlap($left, $right)) {
                    continue;
                }

                $daysBetween = abs((int) $left->getEntryDate()->diff($right->getEntryDate())->format('%r%a'));
                if ($daysBetween < $this->minimumDaysBetweenGroupExams) {
                    $conflicts[] = new ExamScheduleConflictResource(
                        'group_exam_interval_conflict',
                        sprintf('Group exams must be at least %d day(s) apart.', $this->minimumDaysBetweenGroupExams),
                        $this->entryIds($left, $right),
                    );
                }
            }
        }

        return $conflicts;
    }

    private function hasGroupOverlap(ExamScheduleEntry $left, ExamScheduleEntry $right): bool
    {
        $rightGroupIds = $this->groupIds($right);

        foreach ($this->groupIds($left) as $groupId) {
            if (in_array($groupId, $rightGroupIds, true)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<int> */
    private function groupIds(ExamScheduleEntry $entry): array
    {
        $groupIds = [];

        foreach ($entry->getGroups() as $entryGroup) {
            $id = $entryGroup->getGroup()->getId();
            if ($id !== null) {
                $groupIds[] = $id;
            }
        }
        sort($groupIds);

        return $groupIds;
    }

    /** @return list<int> */
    private function entryIds(ExamScheduleEntry ...$entries): array
    {
        $ids = [];

        foreach ($entries as $entry) {
            $id = $entry->getId();
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return $ids;
    }
}
