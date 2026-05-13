<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Admin\TeacherSubjectRequestDto;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\TeacherSubject;

final class TeacherSubjectService extends AbstractEntityService
{
    /** @return array{items: list<array<string, mixed>>} */
    public function list(): array
    {
        return ['items' => array_map($this->serialize(...), $this->listEntities(TeacherSubject::class))];
    }

    /** @return array<string, mixed> */
    public function get(int $id): array
    {
        return $this->serialize($this->getEntity(TeacherSubject::class, $id));
    }

    /** @return array<string, mixed> */
    public function create(TeacherSubjectRequestDto $data): array
    {
        $teacherSubject = new TeacherSubject(
            $this->getEntity(Teacher::class, $this->positiveInt($data->teacherId)),
            $this->getEntity(Subject::class, $this->positiveInt($data->subjectId)),
        );
        $this->save($teacherSubject);

        return $this->serialize($teacherSubject);
    }

    /** @return array<string, mixed> */
    public function update(int $id, TeacherSubjectRequestDto $data): array
    {
        $teacherSubject = $this->getEntity(TeacherSubject::class, $id);

        if ($data->has('teacherId')) {
            $teacherSubject->setTeacher($this->getEntity(Teacher::class, $this->positiveInt($data->teacherId)));
        }
        if ($data->has('subjectId')) {
            $teacherSubject->setSubject($this->getEntity(Subject::class, $this->positiveInt($data->subjectId)));
        }

        $this->flush();

        return $this->serialize($teacherSubject);
    }

    public function deleteById(int $id): void
    {
        $this->delete($this->getEntity(TeacherSubject::class, $id));
    }

    /** @return array<string, mixed> */
    private function serialize(TeacherSubject $teacherSubject): array
    {
        return ['id' => $teacherSubject->getId(), 'teacherId' => $teacherSubject->getTeacher()->getId(), 'subjectId' => $teacherSubject->getSubject()->getId()];
    }
}
