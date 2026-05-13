<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class SemesterResource
{
    public function __construct(
        public ?int $id,
        public ?int $academicYearId,
        public int $number,
        public string $startsAt,
        public string $endsAt,
        public string $firstWeekParity,
    ) {}
}
