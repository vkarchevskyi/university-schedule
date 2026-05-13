<?php

declare(strict_types=1);

namespace App\Service\Room;

use App\Dto\Admin\RoomRequestDto;
use App\Entity\Room;
use App\Resource\Admin\RoomResource;
use App\Resource\Admin\RoomResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class CreateRoomService extends AbstractEntityService
{
    public function __construct(private readonly RoomResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(RoomRequestDto $data): RoomResource
    {
        $room = new Room($this->string($data->name), $this->string($data->type), $this->positiveInt($data->capacity));
        $this->save($room);

        return $this->mapper->map($room);
    }
}
