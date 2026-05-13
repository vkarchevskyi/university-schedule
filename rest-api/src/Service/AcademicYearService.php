<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Admin\AcademicYearRequestDto;
use App\Exception\ApiException;
use App\Entity\AcademicYear;

final class AcademicYearService extends AbstractEntityService
{
    /** @return array{items: list<array<string, mixed>>} */
    public function list(): array
    {
        return ['items' => array_map($this->serialize(...), $this->listEntities(AcademicYear::class))];
    }

    /** @return array<string, mixed> */
    public function get(int $id): array
    {
        return $this->serialize($this->getEntity(AcademicYear::class, $id));
    }

    /** @return array<string, mixed> */
    public function create(AcademicYearRequestDto $data): array
    {
        $academicYear = new AcademicYear($this->string($data->name), $this->date($data->startsAt), $this->date($data->endsAt));
        $this->validateRange($academicYear);
        $this->save($academicYear);

        return $this->serialize($academicYear);
    }

    /** @return array<string, mixed> */
    public function update(int $id, AcademicYearRequestDto $data): array
    {
        $academicYear = $this->getEntity(AcademicYear::class, $id);

        if ($data->has('name')) {
            $academicYear->setName($this->string($data->name));
        }
        if ($data->has('startsAt')) {
            $academicYear->setStartsAt($this->date($data->startsAt));
        }
        if ($data->has('endsAt')) {
            $academicYear->setEndsAt($this->date($data->endsAt));
        }

        $this->validateRange($academicYear);
        $this->flush();

        return $this->serialize($academicYear);
    }

    public function deleteById(int $id): void
    {
        $this->delete($this->getEntity(AcademicYear::class, $id));
    }

    private function validateRange(AcademicYear $academicYear): void
    {
        if ($academicYear->getStartsAt() >= $academicYear->getEndsAt()) {
            throw ApiException::validation(['endsAt' => 'End date must be after start date.']);
        }
    }

    /** @return array<string, mixed> */
    private function serialize(AcademicYear $academicYear): array
    {
        return ['id' => $academicYear->getId(), 'name' => $academicYear->getName(), 'startsAt' => $academicYear->getStartsAt()->format('Y-m-d'), 'endsAt' => $academicYear->getEndsAt()->format('Y-m-d')];
    }
}
