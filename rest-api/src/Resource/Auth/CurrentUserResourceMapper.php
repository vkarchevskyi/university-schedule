<?php

declare(strict_types=1);

namespace App\Resource\Auth;

use App\Entity\User;

final readonly class CurrentUserResourceMapper
{
    public function map(User $user): CurrentUserResource
    {
        $userId = $user->getId();
        if ($userId === null) {
            throw new \LogicException('Expected persisted user.');
        }

        return new CurrentUserResource(new UserProfileResource(
            $userId,
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail(),
            $user->getRole()->value,
        ));
    }
}
