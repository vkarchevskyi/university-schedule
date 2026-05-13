<?php

declare(strict_types=1);

namespace App\Service\TeacherSubject;

use App\Entity\TeacherSubject;
use App\Resource\Admin\TeacherSubjectResource;
use App\Resource\Admin\TeacherSubjectResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class GetTeacherSubjectService extends AbstractEntityService
{
    public function __construct(private readonly TeacherSubjectResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function get(int $id): TeacherSubjectResource
    {
        return $this->mapper->map($this->getEntity(TeacherSubject::class, $id));
    }
}
