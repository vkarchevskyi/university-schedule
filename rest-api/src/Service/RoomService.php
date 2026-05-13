<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Admin\RoomRequestDto;
use App\Entity\Room;

final class RoomService extends AbstractEntityService
{
    /** @return array{items: list<array<string, mixed>>} */
    public function list(): array
    {
        return ['items' => array_map($this->serialize(...), $this->listEntities(Room::class))];
    }

    /** @return array<string, mixed> */
    public function get(int $id): array
    {
        return $this->serialize($this->getEntity(Room::class, $id));
    }

    /** @return array<string, mixed> */
    public function create(RoomRequestDto $data): array
    {
        $room = new Room($this->string($data->name), $this->string($data->type), $this->positiveInt($data->capacity));
        $this->save($room);

        return $this->serialize($room);
    }

    /** @return array<string, mixed> */
    public function update(int $id, RoomRequestDto $data): array
    {
        $room = $this->getEntity(Room::class, $id);

        if ($data->has('name')) {
            $room->setName($this->string($data->name));
        }
        if ($data->has('type')) {
            $room->setType($this->string($data->type));
        }
        if ($data->has('capacity')) {
            $room->setCapacity($this->positiveInt($data->capacity));
        }

        $this->flush();

        return $this->serialize($room);
    }

    public function deleteById(int $id): void
    {
        $this->delete($this->getEntity(Room::class, $id));
    }

    /** @return array<string, mixed> */
    private function serialize(Room $room): array
    {
        return ['id' => $room->getId(), 'name' => $room->getName(), 'type' => $room->getType(), 'capacity' => $room->getCapacity()];
    }
}
