<?php

declare(strict_types=1);

namespace App\Service\PublicSchedule;

use App\Entity\Teacher;
use App\Resource\Public\ResourceCollection;
use App\Resource\Public\TeacherResource;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ListPublicTeachersService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function list(): ResourceCollection
    {
        $teachers = $this->entityManager->getRepository(Teacher::class)->findBy([], ['lastName' => 'ASC', 'firstName' => 'ASC', 'id' => 'ASC']);

        return new ResourceCollection(array_values(array_map(fn(Teacher $teacher): TeacherResource => new TeacherResource(
            $teacher->getId(),
            $teacher->getFirstName(),
            $teacher->getLastName(),
            $teacher->getDepartment(),
        ), $teachers)));
    }
}
