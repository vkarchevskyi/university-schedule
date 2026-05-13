<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Admin\SemesterRequestDto;
use App\Exception\ApiException;
use App\Entity\AcademicYear;
use App\Entity\Semester;

final class SemesterService extends AbstractEntityService
{
    /** @return array{items: list<array<string, mixed>>} */
    public function list(): array
    {
        return ['items' => array_map($this->serialize(...), $this->listEntities(Semester::class))];
    }

    /** @return array<string, mixed> */
    public function get(int $id): array
    {
        return $this->serialize($this->getEntity(Semester::class, $id));
    }

    /** @return array<string, mixed> */
    public function create(SemesterRequestDto $data): array
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

        return $this->serialize($semester);
    }

    /** @return array<string, mixed> */
    public function update(int $id, SemesterRequestDto $data): array
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

        return $this->serialize($semester);
    }

    public function deleteById(int $id): void
    {
        $this->delete($this->getEntity(Semester::class, $id));
    }

    private function validateRange(Semester $semester): void
    {
        if ($semester->getStartsAt() >= $semester->getEndsAt()) {
            throw ApiException::validation(['endsAt' => 'End date must be after start date.']);
        }
    }

    /** @return array<string, mixed> */
    private function serialize(Semester $semester): array
    {
        return ['id' => $semester->getId(), 'academicYearId' => $semester->getAcademicYear()?->getId(), 'number' => $semester->getNumber(), 'startsAt' => $semester->getStartsAt()->format('Y-m-d'), 'endsAt' => $semester->getEndsAt()->format('Y-m-d'), 'firstWeekParity' => strtolower($semester->getFirstWeekParity()->name)];
    }
}
