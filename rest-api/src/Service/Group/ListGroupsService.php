<?php

declare(strict_types=1);

namespace App\Service\Group;

use App\Entity\Group as StudentGroup;
use App\Resource\Admin\GroupResourceMapper;
use App\Resource\Admin\ResourceCollection;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class ListGroupsService extends AbstractEntityService
{
    public function __construct(private readonly GroupResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function list(): ResourceCollection
    {
        return new ResourceCollection(array_map($this->mapper->map(...), $this->listEntities(StudentGroup::class)));
    }
}
