<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class ExamScheduleConflictResource
{
    /**
     * @param list<int> $entryIds
     */
    public function __construct(
        public string $type,
        public string $message,
        public array $entryIds,
    ) {}
}
