<?php

declare(strict_types=1);

namespace App\Service\Teacher;

use App\Entity\Teacher;
use App\Resource\Admin\ResourceCollection;
use App\Resource\Admin\TeacherResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class ListTeachersService extends AbstractEntityService
{
    public function __construct(private readonly TeacherResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function list(): ResourceCollection
    {
        return new ResourceCollection(array_map($this->mapper->map(...), $this->listEntities(Teacher::class)));
    }
}
