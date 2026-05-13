<?php

declare(strict_types=1);

namespace App\Service\Schedule;

use App\Entity\Schedule;
use App\Resource\Admin\ScheduleResource;
use App\Resource\Admin\ScheduleResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class GetScheduleService extends AbstractEntityService
{
    public function __construct(private readonly ScheduleResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function get(int $id): ScheduleResource
    {
        return $this->mapper->map($this->getEntity(Schedule::class, $id));
    }
}
