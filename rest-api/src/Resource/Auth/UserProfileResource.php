<?php

declare(strict_types=1);

namespace App\Resource\Auth;

final readonly class UserProfileResource
{
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $role,
    ) {}
}
