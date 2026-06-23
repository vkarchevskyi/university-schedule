<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Group as StudentGroup;
use App\Entity\Room;
use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use App\Entity\ScheduleEntryGroup;
use App\Entity\ScheduleEntryTeachingLoad;
use App\Entity\Semester;
use App\Entity\TeachingLoad;
use App\Entity\TimeSlot;
use App\Entity\User;
use App\Enum\LessonType;
use App\Enum\ScheduleStatus;
use App\Enum\WeekParity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class SemesterOneTimetableFixtures extends Fixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [AppFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $data = SemesterOneTimetableData::load();
        $semester = $this->semester($manager);
        $admin = $this->admin($manager);
        $groups = $this->groupsByName($manager);
        $rooms = $this->roomsByName($manager);
        $timeSlots = $this->timeSlotsByNumber($manager);
        $teachingLoads = $this->teachingLoadsByKey($manager, $semester);

        $publishedAt = new \DateTimeImmutable('2025-09-15T10:00:00+00:00');
        $schedule = new Schedule(
            $semester,
            ScheduleStatus::Published,
            $semester->getStartsAt(),
            $semester->getEndsAt(),
            $admin,
            $publishedAt,
            $publishedAt,
        );
        $manager->persist($schedule);

        $created = 0;
        $skipped = [
            'group' => 0,
            'room' => 0,
            'timeSlot' => 0,
            'teachingLoad' => 0,
        ];

        foreach (SemesterOneTimetableData::mergeEntries($data['entries']) as $row) {
            $entryGroups = [];
            $entryTeachingLoads = [];

            foreach ($row['groups'] as $groupName) {
                $group = $groups[$groupName] ?? null;

                if (!$group instanceof StudentGroup) {
                    ++$skipped['group'];
                    continue 2;
                }

                $subgroup = isset($row['subgroup']) ? (int) $row['subgroup'] : null;
                $teachingLoad = $teachingLoads[$this->teachingLoadKey(
                    $groupName,
                    (string) $row['subject'],
                    (string) $row['teacherLastName'],
                    (string) $row['teacherFirstName'],
                    (string) $row['lessonType'],
                    $subgroup,
                )] ?? null;

                if (!$teachingLoad instanceof TeachingLoad) {
                    ++$skipped['teachingLoad'];
                    continue 2;
                }

                $entryGroups[] = $group;
                $entryTeachingLoads[] = $teachingLoad;
            }

            $room = $rooms[$row['room']] ?? null;

            if (!$room instanceof Room) {
                ++$skipped['room'];
                continue;
            }

            $timeSlot = $timeSlots[(int) $row['timeSlotNumber']] ?? null;

            if (!$timeSlot instanceof TimeSlot) {
                ++$skipped['timeSlot'];
                continue;
            }

            $primaryLoad = $entryTeachingLoads[0];
            $entry = new ScheduleEntry(
                $schedule,
                $primaryLoad->getSubject(),
                $primaryLoad->getTeacher(),
                $primaryLoad->getLessonType(),
                $room,
                $timeSlot,
                (int) $row['dayOfWeek'],
                $this->weekParity((string) $row['weekParity']),
                isset($row['subgroup']) ? (int) $row['subgroup'] : null,
            );
            $schedule->addEntry($entry);

            foreach ($entryGroups as $group) {
                $entry->addGroup(new ScheduleEntryGroup($entry, $group));
            }

            foreach ($entryTeachingLoads as $teachingLoad) {
                $entry->addTeachingLoad(new ScheduleEntryTeachingLoad($entry, $teachingLoad));
            }

            $inferredSubgroup = $this->inferSubgroup($entryTeachingLoads);
            if ($entry->getSubgroup() === null && $inferredSubgroup !== null) {
                $entry->setSubgroup($inferredSubgroup);
            }

            $manager->persist($entry);
            ++$created;
        }

        $manager->flush();

        fwrite(
            STDERR,
            sprintf(
                "SemesterOneTimetableFixtures: created %d entries, skipped group=%d room=%d timeSlot=%d teachingLoad=%d\n",
                $created,
                $skipped['group'],
                $skipped['room'],
                $skipped['timeSlot'],
                $skipped['teachingLoad'],
            ),
        );
    }

    private function semester(ObjectManager $manager): Semester
    {
        $semester = $manager->getRepository(Semester::class)->createQueryBuilder('semester')
            ->join('semester.academicYear', 'year')
            ->where('year.name = :yearName')
            ->andWhere('semester.number = :number')
            ->setParameter('yearName', '2025/2026')
            ->setParameter('number', 1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$semester instanceof Semester) {
            throw new \RuntimeException('Semester 1 of academic year 2025/2026 was not found.');
        }

        return $semester;
    }

    private function admin(ObjectManager $manager): User
    {
        $admin = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        if (!$admin instanceof User) {
            throw new \RuntimeException('Admin user was not found.');
        }

        return $admin;
    }

    /** @return array<string, StudentGroup> */
    private function groupsByName(ObjectManager $manager): array
    {
        $indexed = [];

        foreach ($manager->getRepository(StudentGroup::class)->findAll() as $group) {
            $indexed[$group->getName()] = $group;
        }

        return $indexed;
    }

    /** @return array<string, Room> */
    private function roomsByName(ObjectManager $manager): array
    {
        $indexed = [];

        foreach ($manager->getRepository(Room::class)->findAll() as $room) {
            $indexed[$room->getName()] = $room;
        }

        return $indexed;
    }

    /** @return array<int, TimeSlot> */
    private function timeSlotsByNumber(ObjectManager $manager): array
    {
        $indexed = [];

        foreach ($manager->getRepository(TimeSlot::class)->findAll() as $timeSlot) {
            $indexed[$timeSlot->getNumber()] = $timeSlot;
        }

        return $indexed;
    }

    /** @return array<string, TeachingLoad> */
    private function teachingLoadsByKey(ObjectManager $manager, Semester $semester): array
    {
        $indexed = [];

        foreach ($manager->getRepository(TeachingLoad::class)->findBy(['semester' => $semester, 'deletedAt' => null]) as $load) {
            $teacher = $load->getTeacher();
            $indexed[$this->teachingLoadKey(
                $load->getGroup()->getName(),
                $load->getSubject()->getName(),
                $teacher->getLastName(),
                $teacher->getFirstName(),
                $this->lessonTypeKey($load->getLessonType()),
                $load->getSubgroup(),
            )] = $load;
        }

        return $indexed;
    }

    private function teachingLoadKey(
        string $groupName,
        string $subjectName,
        string $teacherLastName,
        string $teacherFirstName,
        string $lessonType,
        ?int $subgroup = null,
    ): string {
        return implode('|', [
            $groupName,
            $subjectName,
            $teacherLastName,
            SemesterOneTimetableData::normalizeInitials($teacherFirstName),
            strtolower($lessonType),
            (string) ($subgroup ?? ''),
        ]);
    }

    private function lessonTypeKey(LessonType $lessonType): string
    {
        return match ($lessonType) {
            LessonType::Lecture => 'lecture',
            LessonType::Laboratory => 'laboratory',
            LessonType::Seminar => 'seminar',
            LessonType::Practical => 'practical',
        };
    }

    /** @param list<TeachingLoad> $teachingLoads */
    private function inferSubgroup(array $teachingLoads): ?int
    {
        $subgroups = [];

        foreach ($teachingLoads as $teachingLoad) {
            $subgroup = $teachingLoad->getSubgroup();

            if ($subgroup === null) {
                continue;
            }

            $subgroups[$subgroup] = true;
        }

        if (count($subgroups) !== 1) {
            return null;
        }

        return (int) array_key_first($subgroups);
    }

    private function weekParity(string $parity): WeekParity
    {
        return match ($parity) {
            'odd' => WeekParity::Odd,
            'even' => WeekParity::Even,
            'both' => WeekParity::Both,
            default => throw new \InvalidArgumentException(sprintf('Unsupported week parity "%s".', $parity)),
        };
    }
}
