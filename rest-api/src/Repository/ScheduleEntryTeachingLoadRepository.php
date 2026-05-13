<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ScheduleEntryTeachingLoad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduleEntryTeachingLoad>
 */
class ScheduleEntryTeachingLoadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduleEntryTeachingLoad::class);
    }
}
