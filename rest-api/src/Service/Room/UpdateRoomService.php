<?php

declare(strict_types=1);

namespace App\Service\Room;

use App\Dto\Admin\RoomRequestDto;
use App\Entity\Room;
use App\Resource\Admin\RoomResource;
use App\Resource\Admin\RoomResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateRoomService extends AbstractEntityService
{
    public function __construct(private readonly RoomResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(int $id, RoomRequestDto $data): RoomResource
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

        return $this->mapper->map($room);
    }
}
