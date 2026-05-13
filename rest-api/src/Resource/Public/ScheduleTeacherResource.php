<?php

declare(strict_types=1);

namespace App\Resource\Public;

final readonly class ScheduleTeacherResource
{
    public function __construct(
        public ?int $id,
        public string $firstName,
        public string $lastName,
    ) {}
}
