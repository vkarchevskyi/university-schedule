<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ExamScheduleGenerationJob;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ExamScheduleGenerationJob> */
final class ExamScheduleGenerationJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExamScheduleGenerationJob::class);
    }

    /** @return list<ExamScheduleGenerationJob> */
    public function findLatest(): array
    {
        return array_values($this->findBy([], ['createdAt' => 'DESC']));
    }
}
