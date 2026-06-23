<?php

declare(strict_types=1);

namespace App\Service\Schedule;

use App\Dto\Admin\ScheduleUpdateRequestDto;
use App\Entity\Schedule;
use App\Entity\Semester;
use App\Enum\ScheduleStatus;
use App\Exception\ApiException;
use App\Resource\Admin\ScheduleResource;
use App\Resource\Admin\ScheduleResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateScheduleService extends AbstractEntityService
{
    public function __construct(
        private readonly ScheduleResourceMapper $mapper,
        private readonly ScheduleAuditLoggerService $auditLogger,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function handle(int $id, ScheduleUpdateRequestDto $data): ScheduleResource
    {
        $schedule = $this->draftSchedule($id);

        if (!$data->has('validFrom')) {
            throw ApiException::validation(['validFrom' => 'Expected valid date.']);
        }

        $beforePayload = $this->auditLogger->schedulePayload($schedule);
        $validFrom = $this->scheduleDate($data->validFrom);
        $semester = $schedule->getSemester();

        if (!$semester instanceof Semester) {
            throw ApiException::validation(['schedule' => 'Schedule semester was not found.']);
        }

        $this->validateSchedulePeriod($semester, $validFrom, $schedule->getValidTo());

        if ($validFrom > $schedule->getValidTo()) {
            throw ApiException::validation(['validFrom' => 'Start date must not be after end date.']);
        }

        $schedule->setValidFrom($validFrom);
        $this->auditLogger->logScheduleUpdated($schedule, $beforePayload);
        $this->flush();

        return $this->mapper->map($schedule);
    }

    private function draftSchedule(int $scheduleId): Schedule
    {
        $schedule = $this->getEntity(Schedule::class, $scheduleId);

        if ($schedule->getStatus() !== ScheduleStatus::Draft) {
            throw ApiException::validation(['schedule' => 'Only draft schedules can be edited.']);
        }

        return $schedule;
    }

    private function scheduleDate(string $value): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if (!$date instanceof \DateTimeImmutable) {
            throw ApiException::validation(['validFrom' => 'Expected valid date.']);
        }

        return $date;
    }

    private function validateSchedulePeriod(Semester $semester, \DateTimeImmutable $validFrom, \DateTimeImmutable $validTo): void
    {
        if ($validFrom < $semester->getStartsAt() || $validTo > $semester->getEndsAt()) {
            throw ApiException::validation(['validFrom' => 'Schedule validity period must be within the semester.']);
        }
    }
}
