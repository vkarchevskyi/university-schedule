<?php

declare(strict_types=1);

namespace App\Service\ExamScheduleGeneration;

use App\Entity\ExamScheduleGenerationJob;
use App\Repository\ExamScheduleGenerationJobRepository;
use App\Resource\Admin\ExamScheduleGenerationJobResourceMapper;
use App\Resource\Admin\ResourceCollection;

final readonly class ListExamScheduleGenerationJobsService
{
    public function __construct(
        private ExamScheduleGenerationJobRepository $jobs,
        private ExamScheduleGenerationJobResourceMapper $mapper,
    ) {}

    public function list(): ResourceCollection
    {
        return new ResourceCollection(array_map(
            fn(ExamScheduleGenerationJob $job) => $this->mapper->map($job),
            $this->jobs->findLatest(),
        ));
    }
}
