<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ExamScheduleEntryGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ExamScheduleEntryGroup> */
final class ExamScheduleEntryGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExamScheduleEntryGroup::class);
    }
}
