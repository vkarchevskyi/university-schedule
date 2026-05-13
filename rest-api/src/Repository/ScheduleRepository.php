<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Schedule;
use App\Enum\ScheduleStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Schedule>
 */
class ScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Schedule::class);
    }

    public function findPublishedForWeek(\DateTimeImmutable $weekStart, \DateTimeImmutable $weekEnd): ?Schedule
    {
        return $this->createQueryBuilder('schedule')
            ->andWhere('schedule.status = :status')
            ->andWhere('schedule.validFrom <= :weekEnd')
            ->andWhere('schedule.validTo >= :weekStart')
            ->setParameter('status', ScheduleStatus::Published->value)
            ->setParameter('weekStart', $weekStart)
            ->setParameter('weekEnd', $weekEnd)
            ->orderBy('schedule.publishedAt', 'DESC')
            ->addOrderBy('schedule.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
