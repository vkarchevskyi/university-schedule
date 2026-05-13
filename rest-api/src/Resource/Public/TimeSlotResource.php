<?php

declare(strict_types=1);

namespace App\Resource\Public;

final readonly class TimeSlotResource
{
    public function __construct(
        public ?int $id,
        public int $number,
        public string $startsAt,
        public string $endsAt,
    ) {}
}
