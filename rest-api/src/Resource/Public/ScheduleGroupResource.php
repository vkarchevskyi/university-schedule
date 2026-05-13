<?php

declare(strict_types=1);

namespace App\Resource\Public;

final readonly class ScheduleGroupResource
{
    public function __construct(
        public ?int $id,
        public string $name,
    ) {}
}
