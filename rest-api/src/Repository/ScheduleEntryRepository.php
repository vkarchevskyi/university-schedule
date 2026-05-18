<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduleEntry>
 */
class ScheduleEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduleEntry::class);
    }

    /**
     * @return list<ScheduleEntry>
     */
    public function findPublicEntriesForFilter(Schedule $schedule, string $type, int $id): array
    {
        $queryBuilder = $this->createQueryBuilder('entry')
            ->distinct()
            ->addSelect('subject', 'teacher', 'room', 'timeSlot', 'entryGroup', 'entryGroupEntity', 'lesson', 'lessonSubject', 'lessonTeacher', 'lessonRoom', 'lessonTimeSlot', 'lessonGroup', 'lessonGroupEntity')
            ->innerJoin('entry.subject', 'subject')
            ->innerJoin('entry.teacher', 'teacher')
            ->innerJoin('entry.room', 'room')
            ->innerJoin('entry.timeSlot', 'timeSlot')
            ->leftJoin('entry.groups', 'entryGroup')
            ->leftJoin('entryGroup.group', 'entryGroupEntity')
            ->leftJoin('entry.lessons', 'lesson')
            ->leftJoin('lesson.subject', 'lessonSubject')
            ->leftJoin('lesson.teacher', 'lessonTeacher')
            ->leftJoin('lesson.room', 'lessonRoom')
            ->leftJoin('lesson.timeSlot', 'lessonTimeSlot')
            ->leftJoin('lesson.groups', 'lessonGroup')
            ->leftJoin('lessonGroup.group', 'lessonGroupEntity')
            ->andWhere('entry.schedule = :schedule')
            ->setParameter('schedule', $schedule)
            ->setParameter('targetId', $id);

        match ($type) {
            'group' => $queryBuilder
                ->innerJoin('entry.groups', 'filterEntryGroup')
                ->innerJoin('filterEntryGroup.group', 'filterGroup')
                ->andWhere('filterGroup.id = :targetId'),
            'teacher' => $queryBuilder->andWhere('teacher.id = :targetId'),
            'room' => $queryBuilder->andWhere('room.id = :targetId'),
            default => $queryBuilder->andWhere('1 = 0'),
        };

        $entries = $queryBuilder
            ->addOrderBy('entry.dayOfWeek', 'ASC')
            ->addOrderBy('timeSlot.number', 'ASC')
            ->addOrderBy('entry.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_values(array_filter($entries, static fn(mixed $entry): bool => $entry instanceof ScheduleEntry));
    }
}
