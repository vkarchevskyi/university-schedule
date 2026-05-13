<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class ScheduleValidationResource
{
    /** @param list<ScheduleValidationConflictResource> $conflicts */
    public function __construct(
        public bool $valid,
        public array $conflicts,
    ) {}
}
