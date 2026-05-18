<?php

declare(strict_types=1);

namespace App\Service\Schedule;

use App\Dto\Admin\ScheduleQueryDto;
use App\Entity\Schedule;
use App\Resource\Admin\ResourceCollection;
use App\Resource\Admin\ScheduleResourceMapper;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;

final class ListSchedulesService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(private readonly ScheduleResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function list(?ScheduleQueryDto $query): ResourceCollection
    {
        $criteria = [];
        if ($query?->semesterId !== null) {
            $criteria['semester'] = $this->positiveInt($query->semesterId);
        }

        $schedules = $this->entityManager->getRepository(Schedule::class)->findBy($criteria, ['createdAt' => 'DESC', 'id' => 'DESC']);

        return new ResourceCollection(array_values(array_map($this->mapper->map(...), $schedules)));
    }
}
