<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class TeachingLoadResource
{
    public function __construct(
        public ?int $id,
        public ?int $semesterId,
        public ?int $groupId,
        public ?int $subjectId,
        public ?int $teacherId,
        public string $lessonType,
        public int $requiredLessonCount,
        public bool $requiresComputerRoom,
        public ?string $deletedAt,
    ) {}
}
