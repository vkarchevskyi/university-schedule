<?php

declare(strict_types=1);

namespace App\Service\ExamScheduleGeneration;

use App\Entity\ExamScheduleGenerationJob;
use App\Exception\ApiException;
use App\Resource\Admin\ExamScheduleGenerationJobResource;
use App\Resource\Admin\ExamScheduleGenerationJobResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class GetExamScheduleGenerationJobService extends AbstractEntityService
{
    public function __construct(
        private readonly ExamScheduleGenerationJobResourceMapper $mapper,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function get(string $id): ExamScheduleGenerationJobResource
    {
        $job = $this->entityManager->find(ExamScheduleGenerationJob::class, $id);

        if (!$job instanceof ExamScheduleGenerationJob) {
            throw ApiException::notFound();
        }

        return $this->mapper->map($job);
    }
}
