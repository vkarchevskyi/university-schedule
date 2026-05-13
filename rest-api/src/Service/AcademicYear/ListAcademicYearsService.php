<?php

declare(strict_types=1);

namespace App\Service\AcademicYear;

use App\Entity\AcademicYear;
use App\Resource\Admin\AcademicYearResourceMapper;
use App\Resource\Admin\ResourceCollection;
use App\Service\AbstractEntityService;

final class ListAcademicYearsService extends AbstractEntityService
{
    public function list(): ResourceCollection
    {
        return new ResourceCollection(array_map(
            $this->mapper->map(...),
            $this->listEntities(AcademicYear::class),
        ));
    }

    public function __construct(private readonly AcademicYearResourceMapper $mapper, \Doctrine\ORM\EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }
}
