<?php

declare(strict_types=1);

namespace App\Service\ScheduleEntry;

use App\Dto\Admin\ScheduleEntryRequestDto;
use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use App\Entity\ScheduleEntryGroup;
use App\Entity\ScheduleEntryTeachingLoad;
use App\Enum\ScheduleStatus;
use App\Exception\ApiException;
use App\Resource\Admin\ScheduleEntryResource;
use App\Resource\Admin\ScheduleEntryResourceMapper;
use App\Service\AbstractEntityService;
use App\Service\Schedule\ScheduleAuditLoggerService;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateScheduleEntryService extends AbstractEntityService
{
    public function __construct(
        private readonly ResolveScheduleEntryDataService $resolver,
        private readonly ValidateScheduleEntryConflictService $conflicts,
        private readonly ScheduleEntryResourceMapper $mapper,
        private readonly ScheduleAuditLoggerService $auditLogger,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function handle(int $scheduleId, int $entryId, ScheduleEntryRequestDto $data): ScheduleEntryResource
    {
        $schedule = $this->draftSchedule($scheduleId);
        $entry = $this->scheduleEntry($schedule, $entryId);
        $beforePayload = $this->auditLogger->entryPayload($entry);
        $resolved = $this->resolver->resolve($schedule, $data, $entry);
        $this->conflicts->validate($schedule, $resolved, $entry);

        $entry->reschedule(
            $resolved->subject,
            $resolved->teacher,
            $resolved->lessonType,
            $resolved->room,
            $resolved->timeSlot,
            $resolved->dayOfWeek,
            $resolved->weekParity,
        );
        if ($data->groupIds !== null) {
            $this->replaceGroups($entry, $resolved);
        }

        if ($data->teachingLoadIds !== null) {
            $this->replaceTeachingLoads($entry, $resolved);
        }

        $this->auditLogger->logEntryUpdated($entry, $beforePayload);
        $this->flush();

        return $this->mapper->map($entry);
    }

    private function draftSchedule(int $scheduleId): Schedule
    {
        $schedule = $this->getEntity(Schedule::class, $scheduleId);

        if ($schedule->getStatus() !== ScheduleStatus::Draft) {
            throw ApiException::validation(['schedule' => 'Only draft schedules can be edited.']);
        }

        return $schedule;
    }

    private function scheduleEntry(Schedule $schedule, int $entryId): ScheduleEntry
    {
        $entry = $this->getEntity(ScheduleEntry::class, $entryId);

        if ($entry->getSchedule() !== $schedule) {
            throw ApiException::notFound();
        }

        return $entry;
    }

    private function replaceGroups(ScheduleEntry $entry, ScheduleEntryData $data): void
    {
        foreach ($entry->getGroups()->toArray() as $group) {
            $entry->removeGroup($group);
            $this->entityManager->remove($group);
        }

        foreach ($data->groups as $group) {
            $entry->addGroup(new ScheduleEntryGroup($entry, $group));
        }
    }

    private function replaceTeachingLoads(ScheduleEntry $entry, ScheduleEntryData $data): void
    {
        foreach ($entry->getTeachingLoads()->toArray() as $teachingLoad) {
            $entry->removeTeachingLoad($teachingLoad);
            $this->entityManager->remove($teachingLoad);
        }

        foreach ($data->teachingLoads as $teachingLoad) {
            $entry->addTeachingLoad(new ScheduleEntryTeachingLoad($entry, $teachingLoad));
        }
    }
}
