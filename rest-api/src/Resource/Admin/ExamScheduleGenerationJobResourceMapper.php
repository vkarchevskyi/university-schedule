<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\ExamScheduleGenerationJob;

final readonly class ExamScheduleGenerationJobResourceMapper
{
    public function map(ExamScheduleGenerationJob $job): ExamScheduleGenerationJobResource
    {
        return new ExamScheduleGenerationJobResource(
            $job->getId(),
            (int) $job->getSemester()->getId(),
            (int) $job->getRequestedBy()->getId(),
            $job->getStatus()->value,
            $job->getGeneratedExamSchedule()?->getId(),
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
