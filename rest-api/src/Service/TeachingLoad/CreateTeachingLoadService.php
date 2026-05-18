<?php

declare(strict_types=1);

namespace App\Service\TeachingLoad;

use App\Dto\Admin\TeachingLoadRequestDto;
use App\Entity\Group as StudentGroup;
use App\Entity\Semester;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\TeachingLoad;
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

        return $this->mapper->map($teachingLoad);
    }
}
