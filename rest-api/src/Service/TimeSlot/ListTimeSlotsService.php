<?php

declare(strict_types=1);

namespace App\Service\TimeSlot;

use App\Entity\TimeSlot;
use App\Resource\Admin\ResourceCollection;
use App\Resource\Admin\TimeSlotResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class ListTimeSlotsService extends AbstractEntityService
{
    public function __construct(private readonly TimeSlotResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function list(): ResourceCollection
    {
        return new ResourceCollection(array_map($this->mapper->map(...), $this->listEntities(TimeSlot::class)));
    }
}
