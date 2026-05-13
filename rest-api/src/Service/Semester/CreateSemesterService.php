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
use Doctrine\ORM\EntityManagerInterface;

final class CreateSemesterService extends AbstractEntityService
{
    public function __construct(private readonly SemesterResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(SemesterRequestDto $data): SemesterResource
    {
        $semester = new Semester(
            $this->getEntity(AcademicYear::class, $this->positiveInt($data->academicYearId)),
            $this->positiveInt($data->number),
            $this->date($data->startsAt),
            $this->date($data->endsAt),
            $this->weekParity($data->firstWeekParity),
        );
        $this->validateRange($semester);
        $this->save($semester);

        return $this->mapper->map($semester);
    }

    private function validateRange(Semester $semester): void
    {
        if ($semester->getStartsAt() >= $semester->getEndsAt()) {
            throw ApiException::validation(['endsAt' => 'End date must be after start date.']);
        }
    }
}
