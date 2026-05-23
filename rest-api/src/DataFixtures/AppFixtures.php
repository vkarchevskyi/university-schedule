<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\AcademicYear;
use App\Entity\Group as StudentGroup;
use App\Entity\Room;
use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use App\Entity\ScheduleEntryGroup;
use App\Entity\ScheduleEntryTeachingLoad;
use App\Entity\Semester;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\TeacherSubject;
use App\Entity\TeachingLoad;
use App\Entity\TimeSlot;
use App\Entity\User;
use App\Enum\LessonType;
use App\Enum\ScheduleStatus;
use App\Enum\UserRole;
use App\Enum\WeekParity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2026-01-15T10:00:00+00:00');
        $admin = $this->user('Admin', 'User', 'admin@example.com', UserRole::Admin, $now);
        $manager->persist($admin);

        for ($i = 1; $i <= 9; ++$i) {
            $manager->persist($this->user('User', (string) $i, sprintf('user%d@example.com', $i), UserRole::User, $now));
        }

        $academicYear = new AcademicYear('2025/2026', new \DateTimeImmutable('2025-09-01'), new \DateTimeImmutable('2026-06-30'));
        $semester = new Semester($academicYear, 2, new \DateTimeImmutable('2026-02-02'), new \DateTimeImmutable('2026-06-30'), WeekParity::Odd);

        $manager->persist($academicYear);
        $manager->persist($semester);

        $groups = [
            new StudentGroup('КН-22', 'Комп\'ютерні науки', 4, 24),
            new StudentGroup('КН-23', 'Комп\'ютерні науки', 3, 26),
        ];

        foreach ($groups as $group) {
            $manager->persist($group);
        }

        $rooms = [
            'lecture' => new Room('Ауд. 101', 'lecture', 60),
            'laboratory' => new Room('Лаб. 201', 'computer', 30),
        ];

        foreach ($rooms as $room) {
            $manager->persist($room);
        }

        $timeSlots = [
            new TimeSlot(1, new \DateTimeImmutable('08:30'), new \DateTimeImmutable('10:00')),
            new TimeSlot(2, new \DateTimeImmutable('10:15'), new \DateTimeImmutable('11:45')),
            new TimeSlot(3, new \DateTimeImmutable('12:15'), new \DateTimeImmutable('13:45')),
            new TimeSlot(4, new \DateTimeImmutable('14:00'), new \DateTimeImmutable('15:30')),
        ];

        foreach ($timeSlots as $timeSlot) {
            $manager->persist($timeSlot);
        }

        $subjects = [
            new Subject('Технології розробки стартапів'),
            new Subject('Проектування інформаційних систем'),
            new Subject('Комп\'ютерна анімація'),
            new Subject('Веб-додатки React.JS'),
        ];

        $teachers = [
            new Teacher('О.В.', 'Ізвалов', 'Комп\'ютерні науки'),
            new Teacher('О.Ф.', 'Баранюк', 'Комп\'ютерні науки'),
            new Teacher('В.М.', 'Васильєва', 'Комп\'ютерні науки'),
            new Teacher('К.Ю.', 'Сурков', 'Комп\'ютерні науки'),
        ];

        foreach ($subjects as $index => $subject) {
            $teacher = $teachers[$index];
            $manager->persist($subject);
            $manager->persist($teacher);
            $manager->persist(new TeacherSubject($teacher, $subject));
        }

        $teachingLoads = [];

        foreach ($groups as $groupIndex => $group) {
            foreach ($subjects as $subjectIndex => $subject) {
                foreach ([LessonType::Lecture, LessonType::Laboratory] as $lessonType) {
                    $teachingLoad = new TeachingLoad(
                        $semester,
                        $group,
                        $subject,
                        $teachers[$subjectIndex],
                        $lessonType,
                        8,
                        $now,
                        $now,
                    );
                    $teachingLoads[$groupIndex][$subjectIndex][$lessonType->name] = $teachingLoad;
                    $manager->persist($teachingLoad);
                }
            }
        }

        $schedule = new Schedule(
            $semester,
            ScheduleStatus::Published,
            new \DateTimeImmutable('2026-02-02'),
            new \DateTimeImmutable('2026-06-30'),
            $admin,
            new \DateTimeImmutable('2026-01-20T10:00:00+00:00'),
            new \DateTimeImmutable('2026-01-20T11:00:00+00:00'),
        );
        $manager->persist($schedule);

        foreach ($subjects as $index => $subject) {
            $teacher = $teachers[$index];
            $lectureEntry = $this->scheduleEntry(
                $schedule,
                $subject,
                $teacher,
                LessonType::Lecture,
                $rooms['lecture'],
                $timeSlots[$index % count($timeSlots)],
                $index + 1,
                $groups[0],
                $teachingLoads[0][$index][LessonType::Lecture->name],
            );
            $laboratoryEntry = $this->scheduleEntry(
                $schedule,
                $subject,
                $teacher,
                LessonType::Laboratory,
                $rooms['laboratory'],
                $timeSlots[($index + 1) % count($timeSlots)],
                $index + 1,
                $groups[0],
                $teachingLoads[0][$index][LessonType::Laboratory->name],
            );

            foreach ([$lectureEntry, $laboratoryEntry] as $entryEntities) {
                foreach ($entryEntities as $entity) {
                    $manager->persist($entity);
                }
            }
        }

        $manager->flush();
    }

    private function user(string $firstName, string $lastName, string $email, UserRole $role, \DateTimeImmutable $createdAt): User
    {
        $user = new User($firstName, $lastName, $email, '', $createdAt, $role);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, 'password'));

        return $user;
    }

    /** @return list<object> */
    private function scheduleEntry(
        Schedule $schedule,
        Subject $subject,
        Teacher $teacher,
        LessonType $lessonType,
        Room $room,
        TimeSlot $timeSlot,
        int $dayOfWeek,
        StudentGroup $group,
        TeachingLoad $teachingLoad,
    ): array {
        $entry = new ScheduleEntry($schedule, $subject, $teacher, $lessonType, $room, $timeSlot, $dayOfWeek, WeekParity::Both);
        $entryGroup = new ScheduleEntryGroup($entry, $group);
        $entryTeachingLoad = new ScheduleEntryTeachingLoad($entry, $teachingLoad);

        $schedule->addEntry($entry);
        $entry->addGroup($entryGroup);
        $entry->addTeachingLoad($entryTeachingLoad);

        return [$entry, $entryGroup, $entryTeachingLoad];
    }
}
