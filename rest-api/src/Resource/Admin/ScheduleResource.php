<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class ScheduleResource
{
    /** @param list<ScheduleEntryResource> $entries */
    public function __construct(
        public ?int $id,
        public ?int $semesterId,
        public string $status,
        public string $validFrom,
        public string $validTo,
        public ?int $createdBy,
        public string $createdAt,
        public ?string $publishedAt,
        public array $entries,
    ) {}
}
