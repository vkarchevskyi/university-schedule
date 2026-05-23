<?php

declare(strict_types=1);

namespace App\Resource\Admin;

final readonly class ActionLogUserResource
{
    public function __construct(
        public ?int $id,
        public string $firstName,
        public string $lastName,
        public string $email,
    ) {}
}
