<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TeacherUnavailability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeacherUnavailability>
 */
class TeacherUnavailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeacherUnavailability::class);
    }
}
