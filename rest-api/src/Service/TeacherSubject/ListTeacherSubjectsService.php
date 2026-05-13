<?php

declare(strict_types=1);

namespace App\Service\TeacherSubject;

use App\Entity\TeacherSubject;
use App\Resource\Admin\ResourceCollection;
use App\Resource\Admin\TeacherSubjectResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class ListTeacherSubjectsService extends AbstractEntityService
{
    public function __construct(private readonly TeacherSubjectResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function list(): ResourceCollection
    {
        return new ResourceCollection(array_map($this->mapper->map(...), $this->listEntities(TeacherSubject::class)));
    }
}
