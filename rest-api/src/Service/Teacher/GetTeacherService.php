<?php

declare(strict_types=1);

namespace App\Service\Teacher;

use App\Entity\Teacher;
use App\Resource\Admin\TeacherResource;
use App\Resource\Admin\TeacherResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class GetTeacherService extends AbstractEntityService
{
    public function __construct(private readonly TeacherResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function get(int $id): TeacherResource
    {
        return $this->mapper->map($this->getEntity(Teacher::class, $id));
    }
}
