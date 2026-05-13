<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ExamScheduleEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ExamScheduleEntry> */
final class ExamScheduleEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExamScheduleEntry::class);
    }
}
