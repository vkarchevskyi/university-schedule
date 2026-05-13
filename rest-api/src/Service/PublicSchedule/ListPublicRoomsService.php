<?php

declare(strict_types=1);

namespace App\Service\PublicSchedule;

use App\Entity\Room;
use App\Resource\Public\ResourceCollection;
use App\Resource\Public\RoomResource;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ListPublicRoomsService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function list(): ResourceCollection
    {
        $rooms = $this->entityManager->getRepository(Room::class)->findBy([], ['name' => 'ASC', 'id' => 'ASC']);

        return new ResourceCollection(array_values(array_map(fn(Room $room): RoomResource => new RoomResource(
            $room->getId(),
            $room->getName(),
            $room->getType(),
            $room->getCapacity(),
        ), $rooms)));
    }
}
