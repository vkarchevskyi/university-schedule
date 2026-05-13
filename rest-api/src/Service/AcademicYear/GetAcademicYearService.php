<?php

declare(strict_types=1);

namespace App\Service\AcademicYear;

use App\Entity\AcademicYear;
use App\Resource\Admin\AcademicYearResource;
use App\Resource\Admin\AcademicYearResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class GetAcademicYearService extends AbstractEntityService
{
    public function __construct(private readonly AcademicYearResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function get(int $id): AcademicYearResource
    {
        return $this->mapper->map($this->getEntity(AcademicYear::class, $id));
    }
}
