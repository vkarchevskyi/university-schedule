<?php

declare(strict_types=1);

namespace App\Service\TimeSlot;

use App\Entity\TimeSlot;
use App\Resource\Admin\TimeSlotResource;
use App\Resource\Admin\TimeSlotResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class GetTimeSlotService extends AbstractEntityService
{
    public function __construct(private readonly TimeSlotResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function get(int $id): TimeSlotResource
    {
        return $this->mapper->map($this->getEntity(TimeSlot::class, $id));
    }
}
