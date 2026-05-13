<?php

declare(strict_types=1);

namespace App\Service\PublicSchedule;

use App\Entity\Group as StudentGroup;
use App\Resource\Public\GroupResource;
use App\Resource\Public\ResourceCollection;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ListPublicGroupsService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function list(): ResourceCollection
    {
        $groups = $this->entityManager->getRepository(StudentGroup::class)->findBy([], ['name' => 'ASC', 'id' => 'ASC']);

        return new ResourceCollection(array_map(fn(StudentGroup $group): GroupResource => new GroupResource(
            $group->getId(),
            $group->getName(),
            $group->getSpeciality(),
            $group->getCourse(),
            $group->getStudentCount(),
        ), $groups));
    }
}
