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

final class CreateTeachingLoadService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(private readonly TeachingLoadResourceMapper $mapper, EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager);
    }

    public function handle(TeachingLoadRequestDto $data): TeachingLoadResource
    {
        $now = new \DateTimeImmutable();
        $subject = $this->getEntity(Subject::class, $this->positiveInt($data->subjectId));
        $teacher = $this->getEntity(Teacher::class, $this->positiveInt($data->teacherId));
        $this->validateTeacherSubject($teacher, $subject);

        $teachingLoad = new TeachingLoad(
            $this->getEntity(Semester::class, $this->positiveInt($data->semesterId)),
            $this->getEntity(StudentGroup::class, $this->positiveInt($data->groupId)),
            $subject,
            $teacher,
            $this->lessonType($data->lessonType),
            $this->positiveInt($data->requiredLessonCount),
            $now,
            $now,
        );
        $this->save($teachingLoad);

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
