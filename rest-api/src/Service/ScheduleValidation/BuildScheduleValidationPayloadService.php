<?php

declare(strict_types=1);

namespace App\Service\ScheduleValidation;

use App\Entity\Schedule;

final readonly class BuildScheduleValidationPayloadService
{
    /** @return array<string, mixed> */
    public function handle(Schedule $schedule): array
    {
        return ['scheduleId' => $schedule->getId()];
    }
}
