<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ScheduleEntryGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduleEntryGroup>
 */
class ScheduleEntryGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduleEntryGroup::class);
    }
}
