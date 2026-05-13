<?php

declare(strict_types=1);

namespace App\Service\TeacherUnavailability;

use App\Entity\TeacherUnavailability;
use App\Resource\Admin\TeacherUnavailabilityResource;
use App\Resource\Admin\TeacherUnavailabilityResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class GetTeacherUnavailabilityService extends AbstractEntityService
{
    public function __construct(private readonly TeacherUnavailabilityResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function get(int $id): TeacherUnavailabilityResource
    {
        return $this->mapper->map($this->getEntity(TeacherUnavailability::class, $id));
    }
}
