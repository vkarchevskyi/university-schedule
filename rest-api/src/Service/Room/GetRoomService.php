<?php

declare(strict_types=1);

namespace App\Service\Room;

use App\Entity\Room;
use App\Resource\Admin\RoomResource;
use App\Resource\Admin\RoomResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class GetRoomService extends AbstractEntityService
{
    public function __construct(private readonly RoomResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function get(int $id): RoomResource
    {
        return $this->mapper->map($this->getEntity(Room::class, $id));
    }
}
