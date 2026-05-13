<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\TeacherSubject;

final readonly class TeacherSubjectResourceMapper
{
    public function map(TeacherSubject $teacherSubject): TeacherSubjectResource
    {
        return new TeacherSubjectResource(
            $teacherSubject->getId(),
            $teacherSubject->getTeacher()->getId(),
            $teacherSubject->getSubject()->getId(),
        );
    }
}
