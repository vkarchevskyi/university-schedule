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
use Doctrine\ORM\EntityManagerInterface;

final class CreateScheduleEntryService extends AbstractEntityService
{
    public function __construct(
        private readonly ResolveScheduleEntryDataService $resolver,
        private readonly ValidateScheduleEntryConflictService $conflicts,
        private readonly ScheduleEntryResourceMapper $mapper,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function handle(int $scheduleId, ScheduleEntryRequestDto $data): ScheduleEntryResource
    {
        $schedule = $this->draftSchedule($scheduleId);
        $resolved = $this->resolver->resolve($schedule, $data);
        $this->conflicts->validate($schedule, $resolved);

        $entry = new ScheduleEntry(
            $schedule,
            $resolved->subject,
            $resolved->teacher,
            $resolved->lessonType,
            $resolved->room,
            $resolved->timeSlot,
            $resolved->dayOfWeek,
            $resolved->weekParity,
        );
        $schedule->addEntry($entry);
        $this->attachRelations($entry, $resolved);
        $this->save($entry);

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

    private function attachRelations(ScheduleEntry $entry, ScheduleEntryData $data): void
    {
        foreach ($data->groups as $group) {
            $entry->addGroup(new ScheduleEntryGroup($entry, $group));
        }

        foreach ($data->teachingLoads as $teachingLoad) {
            $entry->addTeachingLoad(new ScheduleEntryTeachingLoad($entry, $teachingLoad));
        }
    }
}
