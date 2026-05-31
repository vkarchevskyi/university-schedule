<?php

declare(strict_types=1);

namespace App\Service\Room;

use App\Dto\Admin\RoomRequestDto;
use App\Entity\Room;
use App\Resource\Admin\RoomResource;
use App\Resource\Admin\RoomResourceMapper;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;

final class CreateRoomService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(private readonly RoomResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(RoomRequestDto $data): RoomResource
    {
        $room = new Room($this->string($data->name), $this->roomType($data->type), $this->positiveInt($data->capacity));
        $this->save($room);

        return $this->mapper->map($room);
    }
}
