<?php

declare(strict_types=1);

namespace App\Resource\Admin;

use App\Entity\Room;

final readonly class RoomResourceMapper
{
    public function map(Room $room): RoomResource
    {
        return new RoomResource(
            $room->getId(),
            $room->getName(),
            $room->getType(),
            $room->getCapacity(),
        );
    }
}
