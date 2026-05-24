<?php

declare(strict_types=1);

namespace App\Service\ScheduleGeneration;

use App\Entity\ScheduleGenerationJob;
use App\Repository\ScheduleGenerationJobRepository;
use App\Resource\Admin\ResourceCollection;
use App\Resource\Admin\ScheduleGenerationJobResourceMapper;

final readonly class ListScheduleGenerationJobsService
{
    public function __construct(
        private ScheduleGenerationJobRepository $jobs,
        private ScheduleGenerationJobResourceMapper $mapper,
    ) {}

    public function list(): ResourceCollection
    {
        return new ResourceCollection(array_map(
            fn(ScheduleGenerationJob $job) => $this->mapper->map($job),
            $this->jobs->findLatest(),
        ));
    }
}
