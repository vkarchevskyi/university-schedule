<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\TeachingLoad;

final readonly class LessonCardResourceMapper
{
    public function __construct(
        private GroupResourceMapper $groups,
        private SubjectResourceMapper $subjects,
        private TeacherResourceMapper $teachers,
    ) {}

    public function map(TeachingLoad $teachingLoad, int $scheduledLessonCount): LessonCardResource
    {
        return new LessonCardResource(
            $teachingLoad->getId(),
            $this->groups->map($teachingLoad->getGroup()),
            $this->subjects->map($teachingLoad->getSubject()),
            $this->teachers->map($teachingLoad->getTeacher()),
            strtolower($teachingLoad->getLessonType()->name),
            $teachingLoad->getRequiredLessonCount(),
            $scheduledLessonCount,
            max($teachingLoad->getRequiredLessonCount() - $scheduledLessonCount, 0),
        );
    }
}
