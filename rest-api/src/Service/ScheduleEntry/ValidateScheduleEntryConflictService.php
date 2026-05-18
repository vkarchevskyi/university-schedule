<?php

declare(strict_types=1);

namespace App\Service\ScheduleEntry;

use App\Domain\Schedule\ScheduleEntryConflictPolicy;
use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use App\Exception\ApiException;

final readonly class ValidateScheduleEntryConflictService
{
    public function __construct(private ScheduleEntryConflictPolicy $policy) {}

    public function validate(Schedule $schedule, ScheduleEntryData $data, ?ScheduleEntry $ignoredEntry = null): void
    {
        $conflict = $this->policy->conflict($schedule, $data, $ignoredEntry);
        if ($conflict !== null) {
            throw ApiException::validation([$conflict->field => $conflict->message]);
        }
    }
}
