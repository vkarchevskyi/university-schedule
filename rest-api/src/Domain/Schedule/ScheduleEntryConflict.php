<?php

declare(strict_types=1);

namespace App\Domain\Schedule;

final readonly class ScheduleEntryConflict
{
    public function __construct(
        public string $field,
        public string $message,
    ) {}
}
