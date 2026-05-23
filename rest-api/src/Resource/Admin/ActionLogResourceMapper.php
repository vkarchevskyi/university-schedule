<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\ActionLog;

final readonly class ActionLogResourceMapper
{
    public function map(ActionLog $log): ActionLogResource
    {
        $user = $log->getUser();

        return new ActionLogResource(
            $log->getId(),
            $log->getAction(),
            $log->getEntityType(),
            $log->getEntityId(),
            $log->getCreatedAt()->format(\DateTimeInterface::ATOM),
            new ActionLogUserResource(
                $user->getId(),
                $user->getFirstName(),
                $user->getLastName(),
                $user->getEmail(),
            ),
            $log->getBeforePayload(),
            $log->getAfterPayload(),
        );
    }
}
