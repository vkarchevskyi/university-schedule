<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Admin\TeachingLoadRequestDto;
use App\Entity\Group as StudentGroup;
use App\Entity\Semester;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\TeachingLoad;

final class TeachingLoadService extends AbstractEntityService
{
    /** @return array{items: list<array<string, mixed>>} */
    public function list(): array
    {
        return ['items' => array_map($this->serialize(...), $this->listEntities(TeachingLoad::class))];
    }

    /** @return array<string, mixed> */
    public function get(int $id): array
    {
        return $this->serialize($this->getEntity(TeachingLoad::class, $id));
    }

    /** @return array<string, mixed> */
    public function create(TeachingLoadRequestDto $data): array
    {
        $now = new \DateTimeImmutable();
        $teachingLoad = new TeachingLoad(
            $this->getEntity(Semester::class, $this->positiveInt($data->semesterId)),
            $this->getEntity(StudentGroup::class, $this->positiveInt($data->groupId)),
            $this->getEntity(Subject::class, $this->positiveInt($data->subjectId)),
            $this->getEntity(Teacher::class, $this->positiveInt($data->teacherId)),
            $this->lessonType($data->lessonType),
            $this->positiveInt($data->requiredLessonCount),
            $now,
            $now,
        );
        $this->save($teachingLoad);

        return $this->serialize($teachingLoad);
    }

    /** @return array<string, mixed> */
    public function update(int $id, TeachingLoadRequestDto $data): array
    {
        $teachingLoad = $this->getEntity(TeachingLoad::class, $id);

        if ($data->has('semesterId')) {
            $teachingLoad->setSemester($this->getEntity(Semester::class, $this->positiveInt($data->semesterId)));
        }
        if ($data->has('groupId')) {
            $teachingLoad->setGroup($this->getEntity(StudentGroup::class, $this->positiveInt($data->groupId)));
        }
        if ($data->has('subjectId')) {
            $teachingLoad->setSubject($this->getEntity(Subject::class, $this->positiveInt($data->subjectId)));
        }
        if ($data->has('teacherId')) {
            $teachingLoad->setTeacher($this->getEntity(Teacher::class, $this->positiveInt($data->teacherId)));
        }
        if ($data->has('lessonType')) {
            $teachingLoad->setLessonType($this->lessonType($data->lessonType));
        }
        if ($data->has('requiredLessonCount')) {
            $teachingLoad->setRequiredLessonCount($this->positiveInt($data->requiredLessonCount));
        }

        $teachingLoad->setUpdatedAt(new \DateTimeImmutable());
        $this->flush();

        return $this->serialize($teachingLoad);
    }

    public function deleteById(int $id): void
    {
        $teachingLoad = $this->getEntity(TeachingLoad::class, $id);
        $now = new \DateTimeImmutable();
        $teachingLoad->setDeletedAt($now);
        $teachingLoad->setUpdatedAt($now);
        $this->flush();
    }

    /** @return array<string, mixed> */
    private function serialize(TeachingLoad $teachingLoad): array
    {
        return ['id' => $teachingLoad->getId(), 'semesterId' => $teachingLoad->getSemester()->getId(), 'groupId' => $teachingLoad->getGroup()->getId(), 'subjectId' => $teachingLoad->getSubject()->getId(), 'teacherId' => $teachingLoad->getTeacher()->getId(), 'lessonType' => strtolower($teachingLoad->getLessonType()->name), 'requiredLessonCount' => $teachingLoad->getRequiredLessonCount(), 'deletedAt' => $teachingLoad->getDeletedAt()?->format(\DateTimeInterface::ATOM)];
    }
}
