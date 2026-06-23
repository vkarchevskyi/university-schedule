<?php

declare(strict_types=1);

namespace App\Service\Schedule;

use App\Entity\ActionLog;
use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use App\Entity\ScheduleEntryGroup;
use App\Entity\ScheduleEntryTeachingLoad;
use App\Entity\User;
use App\Exception\ApiException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class ScheduleAuditLoggerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {}

    public function logScheduleCreated(Schedule $schedule): void
    {
        $this->persist(
            'schedule.created',
            'schedule',
            $this->scheduleId($schedule),
            null,
            $this->schedulePayload($schedule),
        );
    }

    public function logScheduleDuplicated(Schedule $schedule, Schedule $sourceSchedule): void
    {
        $this->persist(
            'schedule.duplicated',
            'schedule',
            $this->scheduleId($schedule),
            [
                'sourceScheduleId' => $sourceSchedule->getId(),
                'entryCount' => $sourceSchedule->getEntries()->count(),
            ],
            $this->schedulePayload($schedule),
        );
    }

    public function logEntryCreated(ScheduleEntry $entry): void
    {
        $this->persist(
            'schedule.entry.created',
            'schedule_entry',
            $this->entryId($entry),
            null,
            $this->entryPayload($entry),
        );
    }

    /** @param array<string, mixed> $beforePayload */
    public function logEntryUpdated(ScheduleEntry $entry, array $beforePayload): void
    {
        $this->persist(
            'schedule.entry.updated',
            'schedule_entry',
            $this->entryId($entry),
            $beforePayload,
            $this->entryPayload($entry),
        );
    }

    /** @param array<string, mixed> $beforePayload */
    public function logEntryDeleted(ScheduleEntry $entry, array $beforePayload): void
    {
        $this->persist(
            'schedule.entry.deleted',
            'schedule_entry',
            $this->entryId($entry),
            $beforePayload,
            null,
        );
    }

    /** @param array<string, mixed> $beforePayload */
    public function logSchedulePublished(Schedule $schedule, array $beforePayload): void
    {
        $this->persist(
            'schedule.published',
            'schedule',
            $this->scheduleId($schedule),
            $beforePayload,
            $this->schedulePayload($schedule),
        );
    }

    /** @param array<string, mixed> $beforePayload */
    public function logScheduleUpdated(Schedule $schedule, array $beforePayload): void
    {
        $this->persist(
            'schedule.updated',
            'schedule',
            $this->scheduleId($schedule),
            $beforePayload,
            $this->schedulePayload($schedule),
        );
    }

    /** @return array<string, mixed> */
    public function schedulePayload(Schedule $schedule): array
    {
        return [
            'id' => $schedule->getId(),
            'semesterId' => $schedule->getSemester()?->getId(),
            'status' => $schedule->getStatus()->value,
            'validFrom' => $schedule->getValidFrom()->format('Y-m-d'),
            'validTo' => $schedule->getValidTo()->format('Y-m-d'),
            'publishedAt' => $schedule->getPublishedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function entryPayload(ScheduleEntry $entry): array
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

        return [
            'id' => $entry->getId(),
            'scheduleId' => $entry->getSchedule()?->getId(),
            'subjectId' => $entry->getSubject()->getId(),
            'teacherId' => $entry->getTeacher()->getId(),
            'lessonType' => strtolower($entry->getLessonType()->name),
            'roomId' => $entry->getRoom()->getId(),
            'timeSlotId' => $entry->getTimeSlot()->getId(),
            'dayOfWeek' => $entry->getDayOfWeek(),
            'weekParity' => strtolower($entry->getWeekParity()->name),
            'subgroup' => $entry->getSubgroup(),
            'groupIds' => $groupIds,
            'teachingLoadIds' => $teachingLoadIds,
        ];
    }

    /**
     * @param array<string, mixed>|null $beforePayload
     * @param array<string, mixed>|null $afterPayload
     */
    private function persist(
        string $action,
        string $entityType,
        int $entityId,
        ?array $beforePayload,
        ?array $afterPayload,
    ): void {
        $this->entityManager->persist(new ActionLog(
            $this->currentUser(),
            $action,
            $entityType,
            $entityId,
            new \DateTimeImmutable(),
            $beforePayload,
            $afterPayload,
        ));
    }

    private function currentUser(): User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw ApiException::http(['error' => 'Authenticated user was not found.'], 401);
        }

        return $user;
    }

    private function scheduleId(Schedule $schedule): int
    {
        $id = $schedule->getId();

        if ($id === null) {
            throw new \LogicException('Schedule must be persisted before audit logging.');
        }

        return $id;
    }

    private function entryId(ScheduleEntry $entry): int
    {
        $id = $entry->getId();

        if ($id === null) {
            throw new \LogicException('Schedule entry must be persisted before audit logging.');
        }

        return $id;
    }

    private function groupId(ScheduleEntryGroup $group): int
    {
        $id = $group->getGroup()->getId();

        if ($id === null) {
            throw new \LogicException('Group must be persisted before audit logging.');
        }

        return $id;
    }

    private function teachingLoadId(ScheduleEntryTeachingLoad $teachingLoad): int
    {
        $id = $teachingLoad->getTeachingLoad()->getId();

        if ($id === null) {
            throw new \LogicException('Teaching load must be persisted before audit logging.');
        }

        return $id;
    }
}
