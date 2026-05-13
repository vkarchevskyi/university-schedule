<?php

declare(strict_types=1);

namespace App\Service\TeachingLoad;

use App\Entity\TeachingLoad;
use App\Resource\Admin\ResourceCollection;
use App\Resource\Admin\TeachingLoadResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class ListTeachingLoadsService extends AbstractEntityService
{
    public function __construct(private readonly TeachingLoadResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function list(): ResourceCollection
    {
        return new ResourceCollection(array_map($this->mapper->map(...), $this->listEntities(TeachingLoad::class)));
    }
}
