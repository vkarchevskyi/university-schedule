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
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;

final class CreateTeacherSubjectService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(private readonly TeacherSubjectResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(TeacherSubjectRequestDto $data): TeacherSubjectResource
    {
        $teacherSubject = new TeacherSubject(
            $this->getEntity(Teacher::class, $this->positiveInt($data->teacherId)),
            $this->getEntity(Subject::class, $this->positiveInt($data->subjectId)),
        );
        $this->save($teacherSubject);

        return $this->mapper->map($teacherSubject);
    }
}
