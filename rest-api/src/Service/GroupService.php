<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Admin\GroupRequestDto;
use App\Entity\Group as StudentGroup;

final class GroupService extends AbstractEntityService
{
    /** @return array{items: list<array<string, mixed>>} */
    public function list(): array
    {
        return ['items' => array_map($this->serialize(...), $this->listEntities(StudentGroup::class))];
    }

    /** @return array<string, mixed> */
    public function get(int $id): array
    {
        return $this->serialize($this->getEntity(StudentGroup::class, $id));
    }

    /** @return array<string, mixed> */
    public function create(GroupRequestDto $data): array
    {
        $group = new StudentGroup($this->string($data->name), $this->string($data->speciality), $this->positiveInt($data->course), $this->nonNegativeInt($data->studentCount));
        $this->save($group);

        return $this->serialize($group);
    }

    /** @return array<string, mixed> */
    public function update(int $id, GroupRequestDto $data): array
    {
        $group = $this->getEntity(StudentGroup::class, $id);

        if ($data->has('name')) {
            $group->setName($this->string($data->name));
        }
        if ($data->has('speciality')) {
            $group->setSpeciality($this->string($data->speciality));
        }
        if ($data->has('course')) {
            $group->setCourse($this->positiveInt($data->course));
        }
        if ($data->has('studentCount')) {
            $group->setStudentCount($this->nonNegativeInt($data->studentCount));
        }

        $this->flush();

        return $this->serialize($group);
    }

    public function deleteById(int $id): void
    {
        $this->delete($this->getEntity(StudentGroup::class, $id));
    }

    /** @return array<string, mixed> */
    private function serialize(StudentGroup $group): array
    {
        return ['id' => $group->getId(), 'name' => $group->getName(), 'speciality' => $group->getSpeciality(), 'course' => $group->getCourse(), 'studentCount' => $group->getStudentCount()];
    }
}
