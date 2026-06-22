<?php

declare(strict_types=1);

namespace App\Service\ScheduleGeneration;

use App\Dto\Admin\ScheduleGenerationRequestDto;
use App\Entity\Room;
use App\Entity\Schedule;
use App\Entity\ScheduleEntryTeachingLoad;
use App\Entity\ScheduleGenerationJob;
use App\Entity\Semester;
use App\Entity\TeacherSubject;
use App\Entity\TeachingLoad;
use App\Entity\TimeSlot;
use App\Entity\User;
use App\Enum\ScheduleStatus;
use App\Enum\WeekParity;
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
        $baseSchedule = $this->resolveBaseSchedule($data->scheduleId, $semester);
        $this->validateGenerationInput($semester, $baseSchedule);
        $user = $this->currentUser();
        $job = new ScheduleGenerationJob($this->uuid(), $semester, $user, new \DateTimeImmutable());

        $this->entityManager->persist($job);
        $this->flush();

        $message = [
            'jobId' => $job->getId(),
            'semesterId' => (int) $semester->getId(),
            'requestedByUserId' => (int) $user->getId(),
        ];
        if ($baseSchedule instanceof Schedule) {
            $message['baseScheduleId'] = (int) $baseSchedule->getId();
        }

        $this->publisher->publish($message);

        return $this->mapper->map($job);
    }

    private function resolveBaseSchedule(?int $scheduleId, Semester $semester): ?Schedule
    {
        if ($scheduleId === null) {
            return null;
        }

        $schedule = $this->getEntity(Schedule::class, $scheduleId);
        if ($schedule->getSemester()?->getId() !== $semester->getId()) {
            throw ApiException::validation(['scheduleId' => 'Schedule must belong to the selected semester.']);
        }

        if ($schedule->getStatus() !== ScheduleStatus::Draft) {
            throw ApiException::validation(['scheduleId' => 'Only draft schedules can be completed with generation.']);
        }

        return $schedule;
    }

    private function validateGenerationInput(Semester $semester, ?Schedule $baseSchedule): void
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

        if ($baseSchedule instanceof Schedule && !$this->hasRemainingTeachingLoads($baseSchedule, $activeLoads)) {
            throw ApiException::validation(['scheduleId' => 'Schedule is already complete.']);
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

    /**
     * @param list<TeachingLoad> $activeLoads
     */
    private function hasRemainingTeachingLoads(Schedule $schedule, array $activeLoads): bool
    {
        foreach ($activeLoads as $teachingLoad) {
            $required = $teachingLoad->getRequiredLessonCount();
            $scheduled = $this->scheduledLessonCount($schedule, $teachingLoad);

            if ($scheduled < $required) {
                return true;
            }
        }

        return false;
    }

    private function scheduledLessonCount(Schedule $schedule, TeachingLoad $teachingLoad): int
    {
        $count = 0;

        foreach ($schedule->getEntries() as $entry) {
            foreach ($entry->getTeachingLoads() as $entryTeachingLoad) {
                if ($this->sameTeachingLoad($entryTeachingLoad, $teachingLoad)) {
                    $count += $this->weekParityCount($entry->getWeekParity());
                }
            }
        }

        return $count;
    }

    private function sameTeachingLoad(ScheduleEntryTeachingLoad $entryTeachingLoad, TeachingLoad $teachingLoad): bool
    {
        return $entryTeachingLoad->getTeachingLoad() === $teachingLoad;
    }

    private function weekParityCount(WeekParity $weekParity): int
    {
        return match ($weekParity) {
            WeekParity::Both => 2,
            WeekParity::Odd, WeekParity::Even => 1,
        };
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
