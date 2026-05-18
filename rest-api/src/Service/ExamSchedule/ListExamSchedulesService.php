<?php

declare(strict_types=1);

namespace App\Service\ExamSchedule;

use App\Dto\Admin\ExamScheduleQueryDto;
use App\Entity\ExamSchedule;
use App\Entity\Semester;
use App\Resource\Admin\ExamScheduleResourceMapper;
use App\Resource\Admin\ResourceCollection;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;

final class ListExamSchedulesService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(
        private readonly ExamScheduleResourceMapper $mapper,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function list(?ExamScheduleQueryDto $query = null): ResourceCollection
    {
        $criteria = ['deletedAt' => null];
        if ($query?->semesterId !== null) {
            $criteria['semester'] = $this->getEntity(Semester::class, $this->positiveInt($query->semesterId));
        }

        $schedules = $this->entityManager->getRepository(ExamSchedule::class)->findBy($criteria, ['id' => 'ASC']);

        return new ResourceCollection(array_values(array_map($this->mapper->map(...), $schedules)));
    }
}
