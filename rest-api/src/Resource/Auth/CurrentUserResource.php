<?php

declare(strict_types=1);

namespace App\Resource\Auth;

final readonly class CurrentUserResource
{
    public function __construct(public UserProfileResource $user) {}
}
