<?php

declare(strict_types=1);

namespace App\Service\Subject;

use App\Entity\Subject;
use App\Resource\Admin\ResourceCollection;
use App\Resource\Admin\SubjectResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class ListSubjectsService extends AbstractEntityService
{
    public function __construct(private readonly SubjectResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function list(): ResourceCollection
    {
        return new ResourceCollection(array_map($this->mapper->map(...), $this->listEntities(Subject::class)));
    }
}
