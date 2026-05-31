<?php

declare(strict_types=1);

namespace App\Service\TeachingLoad;

use App\Dto\Admin\TeachingLoadRequestDto;
use App\Entity\Group as StudentGroup;
use App\Entity\Semester;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\TeacherSubject;
use App\Entity\TeachingLoad;
use App\Exception\ApiException;
use App\Resource\Admin\TeachingLoadResource;
use App\Resource\Admin\TeachingLoadResourceMapper;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;

final class UpdateTeachingLoadService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(private readonly TeachingLoadResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(int $id, TeachingLoadRequestDto $data): TeachingLoadResource
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
        if ($data->has('requiresComputerRoom')) {
            $teachingLoad->setRequiresComputerRoom($data->requiresComputerRoom === true);
        }

        $this->validateTeacherSubject($teachingLoad->getTeacher(), $teachingLoad->getSubject());
        $teachingLoad->setUpdatedAt(new \DateTimeImmutable());
        $this->flush();

        return $this->mapper->map($teachingLoad);
    }

    private function validateTeacherSubject(Teacher $teacher, Subject $subject): void
    {
        $assignment = $this->entityManager->getRepository(TeacherSubject::class)->findOneBy([
            'teacher' => $teacher,
            'subject' => $subject,
        ]);

        if (!$assignment instanceof TeacherSubject) {
            throw ApiException::validation(['teacherId' => 'Teacher is not assigned to this subject.']);
        }
    }
}
