<?php

declare(strict_types=1);

namespace App\Service\Subject;

use App\Entity\Subject;
use App\Resource\Admin\SubjectResource;
use App\Resource\Admin\SubjectResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class GetSubjectService extends AbstractEntityService
{
    public function __construct(private readonly SubjectResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function get(int $id): SubjectResource
    {
        return $this->mapper->map($this->getEntity(Subject::class, $id));
    }
}
