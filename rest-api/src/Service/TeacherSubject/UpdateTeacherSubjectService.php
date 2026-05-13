<?php

declare(strict_types=1);

namespace App\Service\TeacherSubject;

use App\Dto\Admin\TeacherSubjectRequestDto;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\TeacherSubject;
use App\Resource\Admin\TeacherSubjectResource;
use App\Resource\Admin\TeacherSubjectResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateTeacherSubjectService extends AbstractEntityService
{
    public function __construct(private readonly TeacherSubjectResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(int $id, TeacherSubjectRequestDto $data): TeacherSubjectResource
    {
        $teacherSubject = $this->getEntity(TeacherSubject::class, $id);

        if ($data->has('teacherId')) {
            $teacherSubject->setTeacher($this->getEntity(Teacher::class, $this->positiveInt($data->teacherId)));
        }
        if ($data->has('subjectId')) {
            $teacherSubject->setSubject($this->getEntity(Subject::class, $this->positiveInt($data->subjectId)));
        }

        $this->flush();

        return $this->mapper->map($teacherSubject);
    }
}
