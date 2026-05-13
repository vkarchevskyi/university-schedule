<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\TeacherUnavailability;

final readonly class TeacherUnavailabilityResourceMapper
{
    public function map(TeacherUnavailability $unavailability): TeacherUnavailabilityResource
    {
        return new TeacherUnavailabilityResource(
            $unavailability->getId(),
            $unavailability->getTeacher()->getId(),
            $unavailability->getDayOfWeek(),
            $unavailability->getUnavailableFrom()->format('H:i'),
            $unavailability->getUnavailableTo()->format('H:i'),
        );
    }
}
