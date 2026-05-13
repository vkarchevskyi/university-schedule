<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class TeacherSubjectResource
{
    public function __construct(
        public ?int $id,
        public ?int $teacherId,
        public ?int $subjectId,
    ) {}
}
