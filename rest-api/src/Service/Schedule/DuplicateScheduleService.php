<?php

declare(strict_types=1);

namespace App\Service\Schedule;

use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use App\Entity\ScheduleEntryGroup;
use App\Entity\ScheduleEntryTeachingLoad;
use App\Entity\User;
use App\Enum\ScheduleStatus;
use App\Exception\ApiException;
use App\Resource\Admin\ScheduleResource;
use App\Resource\Admin\ScheduleResourceMapper;
use App\Service\AbstractEntityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class DuplicateScheduleService extends AbstractEntityService
{
    public function __construct(
        private readonly ScheduleResourceMapper $mapper,
        private readonly ScheduleAuditLoggerService $auditLogger,
        private readonly Security $security,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($entityManager);
    }

    public function handle(int $sourceScheduleId): ScheduleResource
    {
        $source = $this->getEntity(Schedule::class, $sourceScheduleId);
        $semester = $source->getSemester();

        if ($semester === null) {
            throw ApiException::validation(['schedule' => 'Schedule semester was not found.']);
        }

        $schedule = new Schedule(
            $semester,
            ScheduleStatus::Draft,
            $source->getValidFrom(),
            $source->getValidTo(),
            $this->currentUser(),
            new \DateTimeImmutable(),
        );

        return $this->entityManager->wrapInTransaction(function () use ($source, $schedule): ScheduleResource {
            $this->save($schedule);

            foreach ($source->getEntries() as $sourceEntry) {
                $entry = new ScheduleEntry(
                    $schedule,
                    $sourceEntry->getSubject(),
                    $sourceEntry->getTeacher(),
                    $sourceEntry->getLessonType(),
                    $sourceEntry->getRoom(),
                    $sourceEntry->getTimeSlot(),
                    $sourceEntry->getDayOfWeek(),
                    $sourceEntry->getWeekParity(),
                    $sourceEntry->getSubgroup(),
                );
                $schedule->addEntry($entry);

                foreach ($sourceEntry->getGroups() as $sourceGroup) {
                    $entry->addGroup(new ScheduleEntryGroup($entry, $sourceGroup->getGroup()));
                }

                foreach ($sourceEntry->getTeachingLoads() as $sourceTeachingLoad) {
                    $entry->addTeachingLoad(new ScheduleEntryTeachingLoad($entry, $sourceTeachingLoad->getTeachingLoad()));
                }

                $this->save($entry);
            }

            $this->auditLogger->logScheduleDuplicated($schedule, $source);
            $this->flush();

            return $this->mapper->map($schedule);
        });
    }

    private function currentUser(): User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw ApiException::http(['error' => 'Authenticated user was not found.'], 401);
        }

        return $user;
    }
}
