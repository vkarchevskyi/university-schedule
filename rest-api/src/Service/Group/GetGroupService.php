<?php

declare(strict_types=1);

namespace App\Service\Group;

use App\Entity\Group as StudentGroup;
use App\Resource\Admin\GroupResource;
use App\Resource\Admin\GroupResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class GetGroupService extends AbstractEntityService
{
    public function __construct(private readonly GroupResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function get(int $id): GroupResource
    {
        return $this->mapper->map($this->getEntity(StudentGroup::class, $id));
    }
}
