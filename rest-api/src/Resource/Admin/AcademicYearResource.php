<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class AcademicYearResource
{
    public function __construct(
        public ?int $id,
        public string $name,
        public string $startsAt,
        public string $endsAt,
    ) {}
}
