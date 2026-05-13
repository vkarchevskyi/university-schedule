<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\Teacher;

final readonly class TeacherResourceMapper
{
    public function map(Teacher $teacher): TeacherResource
    {
        return new TeacherResource(
            $teacher->getId(),
            $teacher->getFirstName(),
            $teacher->getLastName(),
            $teacher->getDepartment(),
        );
    }
}
