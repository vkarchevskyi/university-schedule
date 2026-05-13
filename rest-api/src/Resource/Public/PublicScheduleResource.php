<?php

declare(strict_types=1);

namespace App\Resource\Public;

final readonly class PublicScheduleResource
{
    /**
     * @param list<ScheduleItemResource> $items
     */
    public function __construct(
        public string $weekStart,
        public string $type,
        public int $id,
        public array $items,
    ) {}
}
