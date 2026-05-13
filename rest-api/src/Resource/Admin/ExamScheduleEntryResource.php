<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class ExamScheduleEntryResource
{
    /**
     * @param list<int> $groupIds
     */
    public function __construct(
        public ?int $id,
        public ?int $examScheduleId,
        public string $type,
        public ?int $subjectId,
        public ?int $teacherId,
        public ?int $roomId,
        public string $entryDate,
        public string $startsAt,
        public array $groupIds,
        public ?string $deletedAt,
    ) {}
}
