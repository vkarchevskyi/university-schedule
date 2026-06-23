<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\AcademicYear;
use App\Entity\Group as StudentGroup;
use App\Entity\Room;
use App\Entity\Semester;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\TeacherSubject;
use App\Entity\TeachingLoad;
use App\Entity\TimeSlot;
use App\Entity\User;
use App\Enum\LessonType;
use App\Enum\RoomType;
use App\Enum\UserRole;
use App\Enum\WeekParity;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    private const DEFAULT_DEPARTMENT = 'Кафедра загальноосвітніх та професійних дисциплін';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable('2025-09-01T09:00:00+00:00');
        $admin = $this->user('Admin', 'User', 'admin@example.com', UserRole::Admin, $now);
        $manager->persist($admin);

        for ($i = 1; $i <= 9; ++$i) {
            $manager->persist($this->user('User', (string) $i, sprintf('user%d@example.com', $i), UserRole::User, $now));
        }

        $academicYear = new AcademicYear('2025/2026', new \DateTimeImmutable('2025-09-01'), new \DateTimeImmutable('2026-06-30'));
        $semester = new Semester($academicYear, 1, new \DateTimeImmutable('2025-09-01'), new \DateTimeImmutable('2026-01-31'), WeekParity::Odd);
        $manager->persist($academicYear);
        $manager->persist($semester);

        $groups = $this->persistGroups($manager);
        $this->persistRooms($manager);
        $this->persistTimeSlots($manager);

        $subjects = [];
        $teachers = [];
        $teacherSubjects = [];

        foreach (self::teachingLoads() as $load) {
            $subjectName = $load['subject'];
            $teacherKey = $load['teacher'];
            $subjects[$subjectName] ??= new Subject($subjectName);
            $teachers[$teacherKey] ??= $this->teacher($teacherKey);

            $assignmentKey = $teacherKey . '|' . $subjectName;
            if (!isset($teacherSubjects[$assignmentKey])) {
                $teacherSubjects[$assignmentKey] = new TeacherSubject($teachers[$teacherKey], $subjects[$subjectName]);
            }
        }

        foreach ($subjects as $subject) {
            $manager->persist($subject);
        }
        foreach ($teachers as $teacher) {
            $manager->persist($teacher);
        }
        foreach ($teacherSubjects as $teacherSubject) {
            $manager->persist($teacherSubject);
        }

        foreach (self::teachingLoads() as $load) {
            $manager->persist(new TeachingLoad(
                $semester,
                $groups[$load['group']],
                $subjects[$load['subject']],
                $teachers[$load['teacher']],
                $this->lessonType($load['lessonType']),
                $load['requiredLessonCount'],
                false,
                $now,
                $now,
                null,
                $load['subgroup'],
            ));
        }

        $manager->flush();
    }

    private function user(string $firstName, string $lastName, string $email, UserRole $role, \DateTimeImmutable $createdAt): User
    {
        $user = new User($firstName, $lastName, $email, '', $createdAt, $role);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, 'password'));

        return $user;
    }

    private function teacher(string $key): Teacher
    {
        [$lastName, $firstName] = explode('|', $key, 2);

        return new Teacher($firstName, $lastName, self::DEFAULT_DEPARTMENT);
    }

    private function lessonType(string $type): LessonType
    {
        return match ($type) {
            'lecture' => LessonType::Lecture,
            'laboratory' => LessonType::Laboratory,
            'seminar' => LessonType::Seminar,
            'practical' => LessonType::Practical,
            default => throw new \LogicException(sprintf('Unsupported lesson type "%s".', $type)),
        };
    }

    /** @return array<string, StudentGroup> */
    private function persistGroups(ObjectManager $manager): array
    {
        $groups = [];

        foreach (self::groups() as $data) {
            $group = new StudentGroup($data['name'], $data['speciality'], $data['course'], $data['studentCount']);
            $groups[$data['name']] = $group;
            $manager->persist($group);
        }

        foreach (SemesterOneTimetableData::load()['groups'] as $data) {
            if (isset($groups[$data['name']])) {
                continue;
            }

            $group = new StudentGroup($data['name'], $data['speciality'], $data['course'], $data['studentCount']);
            $groups[$data['name']] = $group;
            $manager->persist($group);
        }

        return $groups;
    }

    private function persistRooms(ObjectManager $manager): void
    {
        foreach (self::rooms() as $data) {
            $manager->persist(new Room($data['name'], $this->roomType($data['type']), $data['capacity']));
        }
    }

    private function roomType(string $type): RoomType
    {
        return match ($type) {
            'lecture', 'classroom', 'online' => RoomType::Lecture,
            'computer' => RoomType::Computer,
            default => throw new \LogicException(sprintf('Unsupported room type "%s".', $type)),
        };
    }

    private function persistTimeSlots(ObjectManager $manager): void
    {
        foreach ([
            [1, '08:20', '09:40'],
            [2, '09:50', '11:10'],
            [3, '11:40', '13:00'],
            [4, '13:10', '14:30'],
            [5, '14:40', '16:00'],
            [6, '16:10', '17:30'],
        ] as [$number, $startsAt, $endsAt]) {
            $manager->persist(new TimeSlot($number, new \DateTimeImmutable($startsAt), new \DateTimeImmutable($endsAt)));
        }
    }

    /** @return list<array{name: string, speciality: string, course: int, studentCount: int}> */
    private static function groups(): array
    {
        return [
            ['name' => 'ІПЗ-25', 'speciality' => 'Інженерія програмного забезпечення', 'course' => 1, 'studentCount' => 25],
            ['name' => 'КН-25', 'speciality' => 'Комп\'ютерні науки', 'course' => 1, 'studentCount' => 25],
            ['name' => 'ММ-25', 'speciality' => 'Менеджмент', 'course' => 1, 'studentCount' => 25],
            ['name' => 'МД-25', 'speciality' => 'Менеджмент', 'course' => 1, 'studentCount' => 25],
            ['name' => 'МР-25', 'speciality' => 'Маркетинг', 'course' => 1, 'studentCount' => 25],
            ['name' => 'ПМ-25', 'speciality' => 'Прикладна механіка', 'course' => 1, 'studentCount' => 25],
            ['name' => 'ФН-25', 'speciality' => 'Фінанси', 'course' => 1, 'studentCount' => 25],
            ['name' => 'КН-24', 'speciality' => 'Комп\'ютерні науки', 'course' => 2, 'studentCount' => 25],
            ['name' => 'ММ-24', 'speciality' => 'Менеджмент', 'course' => 2, 'studentCount' => 25],
            ['name' => 'МД-24', 'speciality' => 'Менеджмент', 'course' => 2, 'studentCount' => 25],
            ['name' => 'МР-24', 'speciality' => 'Маркетинг', 'course' => 2, 'studentCount' => 25],
            ['name' => 'ПМ-24', 'speciality' => 'Прикладна механіка', 'course' => 2, 'studentCount' => 25],
            ['name' => 'ФН-24', 'speciality' => 'Фінанси', 'course' => 2, 'studentCount' => 25],
            ['name' => 'КН-23', 'speciality' => 'Комп\'ютерні науки', 'course' => 3, 'studentCount' => 25],
            ['name' => 'МД-23', 'speciality' => 'Менеджмент', 'course' => 3, 'studentCount' => 25],
            ['name' => 'МР-23', 'speciality' => 'Маркетинг', 'course' => 3, 'studentCount' => 25],
            ['name' => 'ПМ-23', 'speciality' => 'Прикладна механіка', 'course' => 3, 'studentCount' => 25],
            ['name' => 'ФН-23', 'speciality' => 'Фінанси', 'course' => 3, 'studentCount' => 25],
            ['name' => 'КН-22', 'speciality' => 'Комп\'ютерні науки', 'course' => 4, 'studentCount' => 25],
            ['name' => 'МД-22', 'speciality' => 'Менеджмент', 'course' => 4, 'studentCount' => 25],
            ['name' => 'МР-22', 'speciality' => 'Маркетинг', 'course' => 4, 'studentCount' => 25],
            ['name' => 'ПМ-22', 'speciality' => 'Прикладна механіка', 'course' => 4, 'studentCount' => 25],
            ['name' => 'ФН-22', 'speciality' => 'Фінанси', 'course' => 4, 'studentCount' => 25],
        ];
    }

    /** @return list<array{name: string, type: string, capacity: int}> */
    private static function rooms(): array
    {
        return [
            ['name' => 'Ауд. 1', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 10', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 11', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 13', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 14', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 15', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 16', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 17', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 2', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 20', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 3', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 4', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 5', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 6', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 8', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Ауд. 9', 'type' => 'classroom', 'capacity' => 200],
            ['name' => 'Бібліотека', 'type' => 'lecture', 'capacity' => 200],
            ['name' => 'Дистанційно', 'type' => 'online', 'capacity' => 500],
            ['name' => 'Конференц-зал', 'type' => 'lecture', 'capacity' => 200],
        ];
    }

    /** @return list<array{group: string, subject: string, teacher: string, lessonType: string, requiredLessonCount: int, subgroup: int|null}> */
    private static function teachingLoads(): array
    {
        $loads = [];

        foreach (SemesterOneTimetableData::load()['teachingLoads'] as $load) {
            $loads[] = [
                'group' => $load['group'],
                'subject' => $load['subject'],
                'teacher' => SemesterOneTimetableData::teacherKey($load['teacherLastName'], $load['teacherFirstName']),
                'lessonType' => $load['lessonType'],
                'requiredLessonCount' => $load['requiredLessonCount'],
                'subgroup' => $load['subgroup'] ?? null,
            ];
        }

        return $loads;
    }
}
