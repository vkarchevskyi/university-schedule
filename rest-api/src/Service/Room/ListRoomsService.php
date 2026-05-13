<?php

declare(strict_types=1);

namespace App\Service\Room;

use App\Entity\Room;
use App\Resource\Admin\ResourceCollection;
use App\Resource\Admin\RoomResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class ListRoomsService extends AbstractEntityService
{
    public function __construct(private readonly RoomResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function list(): ResourceCollection
    {
        return new ResourceCollection(array_map($this->mapper->map(...), $this->listEntities(Room::class)));
    }
}
