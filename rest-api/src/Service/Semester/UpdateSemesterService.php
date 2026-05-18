<?php

declare(strict_types=1);

namespace App\Service\Semester;

use App\Dto\Admin\SemesterRequestDto;
use App\Entity\AcademicYear;
use App\Entity\Semester;
use App\Exception\ApiException;
use App\Resource\Admin\SemesterResource;
use App\Resource\Admin\SemesterResourceMapper;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateSemesterService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(private readonly SemesterResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(int $id, SemesterRequestDto $data): SemesterResource
    {
        $semester = $this->getEntity(Semester::class, $id);

        if ($data->has('academicYearId')) {
            $semester->setAcademicYear($this->getEntity(AcademicYear::class, $this->positiveInt($data->academicYearId)));
        }
        if ($data->has('number')) {
            $semester->setNumber($this->positiveInt($data->number));
        }
        if ($data->has('startsAt')) {
            $semester->setStartsAt($this->date($data->startsAt));
        }
        if ($data->has('endsAt')) {
            $semester->setEndsAt($this->date($data->endsAt));
        }
        if ($data->has('firstWeekParity')) {
            $semester->setFirstWeekParity($this->weekParity($data->firstWeekParity));
        }

        $this->validateRange($semester);
        $this->flush();

        return $this->mapper->map($semester);
    }

    private function validateRange(Semester $semester): void
    {
        if ($semester->getStartsAt() >= $semester->getEndsAt()) {
            throw ApiException::validation(['endsAt' => 'End date must be after start date.']);
        }
    }
}
