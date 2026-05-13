<?php

declare(strict_types=1);

namespace App\Service\ScheduleGeneration;

interface ScheduleGenerationPublisherInterface
{
    /** @param array{jobId: string, semesterId: int, requestedByAdminId: int} $message */
    public function publish(array $message): void;
}
