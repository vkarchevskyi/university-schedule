<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Admin\TeacherRequestDto;
use App\Entity\Teacher;

final class TeacherService extends AbstractEntityService
{
    /** @return array{items: list<array<string, mixed>>} */
    public function list(): array
    {
        return ['items' => array_map($this->serialize(...), $this->listEntities(Teacher::class))];
    }

    /** @return array<string, mixed> */
    public function get(int $id): array
    {
        return $this->serialize($this->getEntity(Teacher::class, $id));
    }

    /** @return array<string, mixed> */
    public function create(TeacherRequestDto $data): array
    {
        $teacher = new Teacher($this->string($data->firstName), $this->string($data->lastName), $this->string($data->department));
        $this->save($teacher);

        return $this->serialize($teacher);
    }

    /** @return array<string, mixed> */
    public function update(int $id, TeacherRequestDto $data): array
    {
        $teacher = $this->getEntity(Teacher::class, $id);

        if ($data->has('firstName')) {
            $teacher->setFirstName($this->string($data->firstName));
        }
        if ($data->has('lastName')) {
            $teacher->setLastName($this->string($data->lastName));
        }
        if ($data->has('department')) {
            $teacher->setDepartment($this->string($data->department));
        }

        $this->flush();

        return $this->serialize($teacher);
    }

    public function deleteById(int $id): void
    {
        $this->delete($this->getEntity(Teacher::class, $id));
    }

    /** @return array<string, mixed> */
    private function serialize(Teacher $teacher): array
    {
        return ['id' => $teacher->getId(), 'firstName' => $teacher->getFirstName(), 'lastName' => $teacher->getLastName(), 'department' => $teacher->getDepartment()];
    }
}
