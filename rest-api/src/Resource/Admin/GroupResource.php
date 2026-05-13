<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class GroupResource
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $speciality,
        public int $course,
        public int $studentCount,
    ) {}
}
