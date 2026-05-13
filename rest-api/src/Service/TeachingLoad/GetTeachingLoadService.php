<?php

declare(strict_types=1);

namespace App\Service\TeachingLoad;

use App\Entity\TeachingLoad;
use App\Resource\Admin\TeachingLoadResource;
use App\Resource\Admin\TeachingLoadResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class GetTeachingLoadService extends AbstractEntityService
{
    public function __construct(private readonly TeachingLoadResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function get(int $id): TeachingLoadResource
    {
        return $this->mapper->map($this->getEntity(TeachingLoad::class, $id));
    }
}
