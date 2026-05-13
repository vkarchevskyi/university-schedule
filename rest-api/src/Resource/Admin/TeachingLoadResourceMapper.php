<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\TeachingLoad;

final readonly class TeachingLoadResourceMapper
{
    public function map(TeachingLoad $teachingLoad): TeachingLoadResource
    {
        return new TeachingLoadResource(
            $teachingLoad->getId(),
            $teachingLoad->getSemester()->getId(),
            $teachingLoad->getGroup()->getId(),
            $teachingLoad->getSubject()->getId(),
            $teachingLoad->getTeacher()->getId(),
            strtolower($teachingLoad->getLessonType()->name),
            $teachingLoad->getRequiredLessonCount(),
            $teachingLoad->getDeletedAt()?->format(\DateTimeInterface::ATOM),
        );
    }
}
