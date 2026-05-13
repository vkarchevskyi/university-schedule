<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\ScheduleGenerationJob;

final readonly class ScheduleGenerationJobResourceMapper
{
    public function map(ScheduleGenerationJob $job): ScheduleGenerationJobResource
    {
        return new ScheduleGenerationJobResource(
            $job->getId(),
            (int) $job->getSemester()->getId(),
            (int) $job->getRequestedBy()->getId(),
            $job->getStatus()->value,
            $job->getGeneratedSchedule()?->getId(),
            $job->getQualityScore(),
            $job->getQualityStatus(),
            $job->getErrorMessage(),
            $job->getDiagnostics(),
            $job->getCreatedAt()->format(DATE_ATOM),
            $job->getStartedAt()?->format(DATE_ATOM),
            $job->getFinishedAt()?->format(DATE_ATOM),
        );
    }
}
