<?php

declare(strict_types=1);

namespace App\Service\TeacherUnavailability;

use App\Entity\TeacherUnavailability;
use App\Resource\Admin\ResourceCollection;
use App\Resource\Admin\TeacherUnavailabilityResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class ListTeacherUnavailabilityService extends AbstractEntityService
{
    public function __construct(private readonly TeacherUnavailabilityResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function list(): ResourceCollection
    {
        return new ResourceCollection(array_map($this->mapper->map(...), $this->listEntities(TeacherUnavailability::class)));
    }
}
