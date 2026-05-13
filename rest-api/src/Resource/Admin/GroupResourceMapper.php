<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\Group as StudentGroup;

final readonly class GroupResourceMapper
{
    public function map(StudentGroup $group): GroupResource
    {
        return new GroupResource(
            $group->getId(),
            $group->getName(),
            $group->getSpeciality(),
            $group->getCourse(),
            $group->getStudentCount(),
        );
    }
}
