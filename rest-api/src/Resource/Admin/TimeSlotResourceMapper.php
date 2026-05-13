<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\TimeSlot;

final readonly class TimeSlotResourceMapper
{
    public function map(TimeSlot $timeSlot): TimeSlotResource
    {
        return new TimeSlotResource(
            $timeSlot->getId(),
            $timeSlot->getNumber(),
            $timeSlot->getStartsAt()->format('H:i'),
            $timeSlot->getEndsAt()->format('H:i'),
        );
    }
}
