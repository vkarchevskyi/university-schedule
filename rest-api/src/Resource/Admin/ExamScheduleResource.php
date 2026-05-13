<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class ExamScheduleResource
{
    /**
     * @param list<ExamScheduleEntryResource> $entries
     */
    public function __construct(
        public ?int $id,
        public ?int $semesterId,
        public string $status,
        public ?int $createdBy,
        public string $createdAt,
        public ?string $publishedAt,
        public ?string $deletedAt,
        public array $entries,
    ) {}
}
