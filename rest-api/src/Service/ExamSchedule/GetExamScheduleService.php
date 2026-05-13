<?php

declare(strict_types=1);

namespace App\Service\ExamSchedule;

use App\Entity\ExamSchedule;
use App\Exception\ApiException;
use App\Resource\Admin\ExamScheduleResource;
use App\Resource\Admin\ExamScheduleResourceMapper;
use Doctrine\ORM\EntityManagerInterface;

final readonly class GetExamScheduleService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExamScheduleResourceMapper $mapper,
    ) {}

    public function get(int $id): ExamScheduleResource
    {
        $schedule = $this->entityManager->find(ExamSchedule::class, $id);

        if (!$schedule instanceof ExamSchedule || $schedule->getDeletedAt() !== null) {
            throw ApiException::notFound();
        }

        return $this->mapper->map($schedule);
    }
}
