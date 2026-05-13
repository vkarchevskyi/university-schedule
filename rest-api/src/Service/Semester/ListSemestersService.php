<?php

declare(strict_types=1);

namespace App\Service\Semester;

use App\Entity\Semester;
use App\Resource\Admin\ResourceCollection;
use App\Resource\Admin\SemesterResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class ListSemestersService extends AbstractEntityService
{
    public function __construct(private readonly SemesterResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function list(): ResourceCollection
    {
        return new ResourceCollection(array_map($this->mapper->map(...), $this->listEntities(Semester::class)));
    }
}
