<?php

declare(strict_types=1);

namespace App\Service\Semester;

use App\Entity\Semester;
use App\Resource\Admin\SemesterResource;
use App\Resource\Admin\SemesterResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class GetSemesterService extends AbstractEntityService
{
    public function __construct(private readonly SemesterResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function get(int $id): SemesterResource
    {
        return $this->mapper->map($this->getEntity(Semester::class, $id));
    }
}
