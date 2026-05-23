<?php

declare(strict_types=1);

namespace App\Service\ScheduleEntry;

use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use App\Enum\ScheduleStatus;
use App\Exception\ApiException;
use App\Service\AbstractEntityService;
use App\Service\Schedule\ScheduleAuditLoggerService;
use Doctrine\ORM\EntityManagerInterface;

final class DeleteScheduleEntryService extends AbstractEntityService
{
    public function __construct(
        private readonly ScheduleAuditLoggerService $auditLogger,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function handle(int $scheduleId, int $entryId): void
    {
        $schedule = $this->getEntity(Schedule::class, $scheduleId);

        if ($schedule->getStatus() !== ScheduleStatus::Draft) {
            throw ApiException::validation(['schedule' => 'Only draft schedules can be edited.']);
        }

        $entry = $this->getEntity(ScheduleEntry::class, $entryId);

        if ($entry->getSchedule() !== $schedule) {
            throw ApiException::notFound();
        }

        $beforePayload = $this->auditLogger->entryPayload($entry);
        $this->auditLogger->logEntryDeleted($entry, $beforePayload);
        $this->delete($entry);
    }
}
