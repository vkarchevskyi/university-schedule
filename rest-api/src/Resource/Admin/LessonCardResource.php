<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class LessonCardResource
{
    public function __construct(
        public ?int $teachingLoadId,
        public GroupResource $group,
        public SubjectResource $subject,
        public TeacherResource $teacher,
        public string $lessonType,
        public int $requiredLessonCount,
        public bool $requiresComputerRoom,
        public int $scheduledLessonCount,
        public int $remainingLessonCount,
    ) {}
}
