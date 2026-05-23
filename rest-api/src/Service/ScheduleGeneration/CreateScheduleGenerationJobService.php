<?php

declare(strict_types=1);

namespace App\Service\ScheduleGeneration;

use App\Dto\Admin\ScheduleGenerationRequestDto;
use App\Entity\Room;
use App\Entity\ScheduleGenerationJob;
use App\Entity\Semester;
use App\Entity\TeacherSubject;
use App\Entity\TimeSlot;
use App\Entity\User;
use App\Exception\ApiException;
use App\Resource\Admin\ScheduleGenerationJobResource;
use App\Resource\Admin\ScheduleGenerationJobResourceMapper;
use App\Service\AbstractEntityService;
use App\Service\InputNormalizerTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class CreateScheduleGenerationJobService extends AbstractEntityService
{
    use InputNormalizerTrait;

    public function __construct(
        private readonly ScheduleGenerationJobResourceMapper $mapper,
        private readonly ScheduleGenerationPublisherInterface $publisher,
        private readonly Security $security,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function handle(ScheduleGenerationRequestDto $data): ScheduleGenerationJobResource
    {
        $semester = $this->getEntity(Semester::class, $this->positiveInt($data->semesterId));
        $this->validateGenerationInput($semester);
        $user = $this->currentUser();
        $job = new ScheduleGenerationJob($this->uuid(), $semester, $user, new \DateTimeImmutable());

        $this->entityManager->persist($job);
        $this->flush();
        $this->publisher->publish([
            'jobId' => $job->getId(),
            'semesterId' => (int) $semester->getId(),
            'requestedByUserId' => (int) $user->getId(),
        ]);

        return $this->mapper->map($job);
    }

    private function validateGenerationInput(Semester $semester): void
    {
        $activeLoads = [];

        foreach ($semester->getTeachingLoads() as $teachingLoad) {
            if ($teachingLoad->getDeletedAt() === null) {
                $activeLoads[] = $teachingLoad;
            }
        }

        if ($activeLoads === []) {
            throw ApiException::validation(['semesterId' => 'Semester has no active teaching loads.']);
        }

        if ($this->entityManager->getRepository(Room::class)->count([]) === 0) {
            throw ApiException::validation(['rooms' => 'At least one room is required for schedule generation.']);
        }

        if ($this->entityManager->getRepository(TimeSlot::class)->count([]) === 0) {
            throw ApiException::validation(['timeSlots' => 'At least one time slot is required for schedule generation.']);
        }

        foreach ($activeLoads as $teachingLoad) {
            $assignment = $this->entityManager->getRepository(TeacherSubject::class)->findOneBy([
                'teacher' => $teachingLoad->getTeacher(),
                'subject' => $teachingLoad->getSubject(),
            ]);

            if (!$assignment instanceof TeacherSubject) {
                throw ApiException::validation(['teachingLoads' => 'Teaching loads must use teachers assigned to their subjects.']);
            }
        }
    }

    private function currentUser(): User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw ApiException::http(['error' => 'Authenticated user was not found.'], 401);
        }

        return $user;
    }

    private function uuid(): string
    {
        $bytes = bin2hex(random_bytes(16));

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($bytes, 0, 8),
            substr($bytes, 8, 4),
            substr($bytes, 12, 4),
            substr($bytes, 16, 4),
            substr($bytes, 20),
        );
    }
}
