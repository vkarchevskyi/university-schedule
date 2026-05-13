<?php

declare(strict_types=1);

namespace App\Service\ScheduleGeneration;

use App\Entity\ScheduleGenerationJob;
use App\Exception\ApiException;
use App\Resource\Admin\ScheduleGenerationJobResource;
use App\Resource\Admin\ScheduleGenerationJobResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class GetScheduleGenerationJobService extends AbstractEntityService
{
    public function __construct(
        private readonly ScheduleGenerationJobResourceMapper $mapper,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function get(string $id): ScheduleGenerationJobResource
    {
        $job = $this->entityManager->find(ScheduleGenerationJob::class, $id);

        if (!$job instanceof ScheduleGenerationJob) {
            throw ApiException::notFound();
        }

        return $this->mapper->map($job);
    }
}
