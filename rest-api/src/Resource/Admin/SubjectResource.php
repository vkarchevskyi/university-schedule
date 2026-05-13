<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class SubjectResource
{
    public function __construct(
        public ?int $id,
        public string $name,
    ) {}
}
