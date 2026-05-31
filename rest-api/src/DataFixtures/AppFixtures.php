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
                $now,
                $now,
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

        return $groups;
    }

    private function persistRooms(ObjectManager $manager): void
    {
        foreach (self::rooms() as $data) {
            $manager->persist(new Room($data['name'], $data['type'], $data['capacity']));
        }
    }

    private function persistTimeSlots(ObjectManager $manager): void
    {
        foreach ([
            [1, '08:30', '10:00'],
            [2, '10:15', '11:45'],
            [3, '12:15', '13:45'],
            [4, '14:00', '15:30'],
            [5, '15:45', '17:15'],
            [6, '17:30', '19:00'],
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
            ['name' => 'ПМ-25', 'speciality' => 'Прикладна механіка', 'course' => 1, 'studentCount' => 25],
            ['name' => 'ФН-25', 'speciality' => 'Фінанси', 'course' => 1, 'studentCount' => 25],
            ['name' => 'КН-24', 'speciality' => 'Комп\'ютерні науки', 'course' => 2, 'studentCount' => 25],
            ['name' => 'ММ-24', 'speciality' => 'Менеджмент', 'course' => 2, 'studentCount' => 25],
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
            ['name' => 'Ауд. 1', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 10', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 11', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 13', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 14', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 15', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 16', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 17', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 2', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 20', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 3', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 4', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 5', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 6', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 8', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Ауд. 9', 'type' => 'classroom', 'capacity' => 30],
            ['name' => 'Бібліотека', 'type' => 'lecture', 'capacity' => 80],
            ['name' => 'Дистанційно', 'type' => 'online', 'capacity' => 500],
            ['name' => 'Конференц-зал', 'type' => 'lecture', 'capacity' => 80],
        ];
    }

    /** @return list<array{group: string, subject: string, teacher: string, lessonType: string, requiredLessonCount: int}> */
    private static function teachingLoads(): array
    {
        return [
            ['group' => 'ІПЗ-25', 'subject' => 'Іноземна мова', 'teacher' => 'Максимова|О.П.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'ІПЗ-25', 'subject' => 'Іноземна мова', 'teacher' => 'Рожкова|Н.Г.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'ІПЗ-25', 'subject' => 'Вища математика', 'teacher' => 'Бондар|О.П.', 'lessonType' => 'lecture', 'requiredLessonCount' => 3],
            ['group' => 'ІПЗ-25', 'subject' => 'Вища математика', 'teacher' => 'Бондар|О.П.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ІПЗ-25', 'subject' => 'Комп\'ютерна графіка', 'teacher' => 'Ізвалов|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 1],
            ['group' => 'ІПЗ-25', 'subject' => 'Комп. графіка', 'teacher' => 'Ізвалов|О.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'ІПЗ-25', 'subject' => 'Комп.графіка', 'teacher' => 'Ізвалов|О.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'ІПЗ-25', 'subject' => 'Правознавство', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ІПЗ-25', 'subject' => 'Правознавство', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ІПЗ-25', 'subject' => 'Програмування', 'teacher' => 'Книшук|А.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ІПЗ-25', 'subject' => 'Програмування', 'teacher' => 'Книшук|А.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 6],
            ['group' => 'ІПЗ-25', 'subject' => 'Теорія алгоритмів', 'teacher' => 'Паращук|С.Д.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ІПЗ-25', 'subject' => 'Теорія алгоритмів', 'teacher' => 'Паращук|С.Д.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ІПЗ-25', 'subject' => 'Українська мова за професійним спрямуванням', 'teacher' => 'Дідковська|Н.А.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'КН-22', 'subject' => 'Віртуальні та доповнена реальність', 'teacher' => 'Ізвалов|О.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 5],
            ['group' => 'КН-22', 'subject' => 'Паралельні та розподіленні обчислення', 'teacher' => 'Паращук|С.Д.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'КН-22', 'subject' => 'Програмування веб-додатків LARAVEL', 'teacher' => 'Зєнов|Д.О.', 'lessonType' => 'practical', 'requiredLessonCount' => 5],
            ['group' => 'КН-22', 'subject' => 'Проектування інформ. систем', 'teacher' => 'Неділько|В.М.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'КН-22', 'subject' => 'Розробка мобільних додатків', 'teacher' => 'Ізвалов|О.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 5],
            ['group' => 'КН-22', 'subject' => 'Системний аналіз', 'teacher' => 'Бондар|О.П.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'КН-22', 'subject' => 'Управління ІТ-проектами', 'teacher' => 'Сурков|К.Ю.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'КН-23', 'subject' => 'Безпека життєдільності', 'teacher' => 'Кравцов|А.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 1],
            ['group' => 'КН-23', 'subject' => 'Безпека життєдільності', 'teacher' => 'Кравцов|А.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 1],
            ['group' => 'КН-23', 'subject' => 'Комп\'ютерні мережі', 'teacher' => 'Баранюк|О.Ф.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'КН-23', 'subject' => 'Комп.мережі', 'teacher' => 'Баранюк|О.Ф.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'КН-23', 'subject' => 'Крос-платформне програмування', 'teacher' => 'Ізвалов|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'КН-23', 'subject' => 'Крос-платформне програмування', 'teacher' => 'Ізвалов|О.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'КН-23', 'subject' => 'Математичні методи дослдіження операцій', 'teacher' => 'Мироненко|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'КН-23', 'subject' => 'Математичні методи дослдіження операцій', 'teacher' => 'Мироненко|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'КН-23', 'subject' => 'Основи підприємництва', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'КН-23', 'subject' => 'Розробка пр. забезп.', 'teacher' => 'Ізвалов|О.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'КН-23', 'subject' => 'Розробка пр.забезпечення', 'teacher' => 'Ізвалов|О.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'КН-23', 'subject' => 'Розробка програмного забезпечення', 'teacher' => 'Ізвалов|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'КН-23', 'subject' => 'Теорія прийняття рішень', 'teacher' => 'Бондар|О.П.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'КН-23', 'subject' => 'Теорія прийняття рішень', 'teacher' => 'Бондар|О.П.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'КН-23', 'subject' => 'Якість програмного забезпечення', 'teacher' => 'Найдьонов|І.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'КН-23', 'subject' => 'Якість програмного забезпечення', 'teacher' => 'Найдьонов|І.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 3],
            ['group' => 'КН-24', 'subject' => 'Іноземна мова', 'teacher' => 'Максимова|О.П.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'КН-24', 'subject' => 'Іноземна мова', 'teacher' => 'Протасова|А.П.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'КН-24', 'subject' => 'Базова військова підготовка', 'teacher' => 'Не визначено|Н.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'КН-24', 'subject' => 'Веб-технології', 'teacher' => 'Сурков|К.Ю.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'КН-24', 'subject' => 'Веб-технології та веб-дизайн', 'teacher' => 'Сурков|К.Ю.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'КН-24', 'subject' => 'Орг-ція баз даних', 'teacher' => 'Книшук|А.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'КН-24', 'subject' => 'Організація баз даних', 'teacher' => 'Книшук|А.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 3],
            ['group' => 'КН-24', 'subject' => 'Організація баз даних', 'teacher' => 'Книшук|А.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'КН-24', 'subject' => 'Програмування', 'teacher' => 'Сурков|К.Ю.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'КН-24', 'subject' => 'Програмування', 'teacher' => 'Сурков|К.Ю.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'КН-24', 'subject' => 'Психологія', 'teacher' => 'Нестеренко|Т.С.', 'lessonType' => 'lecture', 'requiredLessonCount' => 1],
            ['group' => 'КН-24', 'subject' => 'Психологія', 'teacher' => 'Нестеренко|Т.С.', 'lessonType' => 'seminar', 'requiredLessonCount' => 1],
            ['group' => 'КН-24', 'subject' => 'Теорія ймовірності та мат.статистика', 'teacher' => 'Мироненко|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'КН-24', 'subject' => 'Теорія ймовірності та мат.статистика', 'teacher' => 'Мироненко|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'КН-25', 'subject' => 'Іноземна мова', 'teacher' => 'Максимова|О.П.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'КН-25', 'subject' => 'Іноземна мова', 'teacher' => 'Рожкова|Н.Г.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'КН-25', 'subject' => 'Вища математика', 'teacher' => 'Бондар|О.П.', 'lessonType' => 'lecture', 'requiredLessonCount' => 3],
            ['group' => 'КН-25', 'subject' => 'Вища математика', 'teacher' => 'Бондар|О.П.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'КН-25', 'subject' => 'Комп\'ютерна графіка', 'teacher' => 'Ізвалов|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 1],
            ['group' => 'КН-25', 'subject' => 'Комп. графіка', 'teacher' => 'Ізвалов|О.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'КН-25', 'subject' => 'Комп.графіка', 'teacher' => 'Ізвалов|О.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'КН-25', 'subject' => 'Правознавство', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'КН-25', 'subject' => 'Правознавство', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'КН-25', 'subject' => 'Програмування', 'teacher' => 'Книшук|А.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'КН-25', 'subject' => 'Програмування', 'teacher' => 'Книшук|А.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 6],
            ['group' => 'КН-25', 'subject' => 'Теорія алгоритмів', 'teacher' => 'Паращук|С.Д.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'КН-25', 'subject' => 'Теорія алгоритмів', 'teacher' => 'Паращук|С.Д.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'КН-25', 'subject' => 'Українська мова за професійним спрямуванням', 'teacher' => 'Дідковська|Н.А.', 'lessonType' => 'seminar', 'requiredLessonCount' => 4],
            ['group' => 'МД-22', 'subject' => 'SALES-менеджмент', 'teacher' => 'Алексеєва|Л.М.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'МД-22', 'subject' => 'Ділова іноземна мова', 'teacher' => 'Максимова|О.П.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'МД-22', 'subject' => 'Маркетингове ціноутворення', 'teacher' => 'Яковенко|Р.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 3],
            ['group' => 'МД-22', 'subject' => 'Рекламний менеджмент', 'teacher' => 'Тертиця|О.О.', 'lessonType' => 'seminar', 'requiredLessonCount' => 3],
            ['group' => 'МД-22', 'subject' => 'Стратегічний менеджмент', 'teacher' => 'Павлова|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 4],
            ['group' => 'МД-22', 'subject' => 'Стратегічний менеджмент', 'teacher' => 'Павлова|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'МД-22', 'subject' => 'Управління якістю', 'teacher' => 'Яковенко|Р.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'МД-22', 'subject' => 'Управління якістю', 'teacher' => 'Яковенко|Р.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'МД-23', 'subject' => 'Господарське законодавство', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'МД-23', 'subject' => 'Господарське законодавство', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 1],
            ['group' => 'МД-23', 'subject' => 'Державне управління та регулювання', 'teacher' => 'Яковенко|Р.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'МД-23', 'subject' => 'Державне управління та регулювання', 'teacher' => 'Яковенко|Р.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'МД-23', 'subject' => 'Ділова іноземна мова - (І підгрупа)', 'teacher' => 'Протасова|А.П.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'МД-23', 'subject' => 'Ділова іноземна мова - (І підгрупа) Ділова іноземна мова - (ІІ підгрупа)', 'teacher' => 'Протасова|А.П.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'МД-23', 'subject' => 'Ділова іноземна мова - (ІІ підгрупа)', 'teacher' => 'Рожкова|Н.Г.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'МД-23', 'subject' => 'Економічний аналіз', 'teacher' => 'Загреба|І.Л.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'МД-23', 'subject' => 'Економічний аналіз', 'teacher' => 'Загреба|І.Л.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'МД-23', 'subject' => 'Лідерство', 'teacher' => 'Нестеренко|Т.С.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'МД-23', 'subject' => 'Лідерство', 'teacher' => 'Нестеренко|Т.С.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'МД-23', 'subject' => 'Менеджмент', 'teacher' => 'Павлова|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'МД-23', 'subject' => 'Менеджмент', 'teacher' => 'Павлова|О.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 1],
            ['group' => 'МД-23', 'subject' => 'Менеджмент', 'teacher' => 'Павлова|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ММ-24', 'subject' => 'Іноземна мова', 'teacher' => 'Максимова|О.П.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'ММ-24', 'subject' => 'Маркетинговий менеджмент', 'teacher' => 'Павлова|О.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 8],
            ['group' => 'ММ-24', 'subject' => 'Стратегічне управління підприємством', 'teacher' => 'Тертиця|О.О.', 'lessonType' => 'practical', 'requiredLessonCount' => 8],
            ['group' => 'ММ-24', 'subject' => 'Управління проектами', 'teacher' => 'Загреба|І.Л.', 'lessonType' => 'practical', 'requiredLessonCount' => 6],
            ['group' => 'ММ-25', 'subject' => 'Іноземна мова', 'teacher' => 'Максимова|О.П.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'ММ-25', 'subject' => 'Інформаційні системи в управлінні', 'teacher' => 'Сурков|К.Ю.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'ММ-25', 'subject' => 'Договірне право', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'ММ-25', 'subject' => 'Кадровий менеджмент', 'teacher' => 'Яковенко|Р.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'ММ-25', 'subject' => 'Менеджмент організацій', 'teacher' => 'Яковенко|Р.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'ММ-25', 'subject' => 'Методологія та організація наукових досліджень', 'teacher' => 'Яковенко|Р.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'ММ-25', 'subject' => 'Управління ризиками', 'teacher' => 'Загреба|І.Л.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'МР-22', 'subject' => 'Маркетингова товарна політика', 'teacher' => 'Алексеєва|Л.М.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'МР-22', 'subject' => 'Маркетингова товарна політика', 'teacher' => 'Алексеєва|Л.М.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'МР-23', 'subject' => 'Маркетинг', 'teacher' => 'Алексеєва|Л.М.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'МР-23', 'subject' => 'Маркетинг', 'teacher' => 'Алексеєва|Л.М.', 'lessonType' => 'practical', 'requiredLessonCount' => 1],
            ['group' => 'МР-23', 'subject' => 'Маркетинг', 'teacher' => 'Алексеєва|Л.М.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'МР-23', 'subject' => 'Маркетингові дослідження', 'teacher' => 'Алексеєва|Л.М.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'МР-23', 'subject' => 'Маркетингові дослідження', 'teacher' => 'Алексеєва|Л.М.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-22', 'subject' => 'Автоматизованя системи керування підприємством', 'teacher' => 'Неділько|В.М.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'ПМ-22', 'subject' => 'Екологія', 'teacher' => 'Не визначено|Н.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-22', 'subject' => 'Екологія', 'teacher' => 'Не визначено|Н.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-22', 'subject' => 'Програмування мехатронних систем', 'teacher' => 'Руденко|Т.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-22', 'subject' => 'Програмування мехатронних систем', 'teacher' => 'Руденко|Т.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 3],
            ['group' => 'ПМ-22', 'subject' => 'Проектування машинобуд заводів', 'teacher' => 'Пузирьов|О.Л.', 'lessonType' => 'lecture', 'requiredLessonCount' => 1],
            ['group' => 'ПМ-22', 'subject' => 'Проектування машинобуд. заводів', 'teacher' => 'Пузирьов|О.Л.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-22', 'subject' => 'Проектування машинобуд. заводів', 'teacher' => 'Пузирьов|О.Л.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-22', 'subject' => 'Теорія автоматичного керування', 'teacher' => 'Гаврилюк|Б.О.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'ПМ-22', 'subject' => 'Технологічна оснастка', 'teacher' => 'Пузирьов|О.Л.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-22', 'subject' => 'Технологічна оснастка', 'teacher' => 'Пузирьов|О.Л.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'ПМ-22', 'subject' => 'Технології машинобудування 2', 'teacher' => 'Кравцов|А.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-22', 'subject' => 'Технології машинобудування 2', 'teacher' => 'Кравцов|А.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'ПМ-23', 'subject' => 'Електротехніка', 'teacher' => 'Гаврилюк|Б.О.', 'lessonType' => 'laboratory', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-23', 'subject' => 'Електротехніка', 'teacher' => 'Гаврилюк|Б.О.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-23', 'subject' => 'Електротехніка', 'teacher' => 'Гаврилюк|Б.О.', 'lessonType' => 'practical', 'requiredLessonCount' => 1],
            ['group' => 'ПМ-23', 'subject' => 'Конструкторське моделювання', 'teacher' => 'Кравцов|А.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-23', 'subject' => 'Конструкторське моделювання', 'teacher' => 'Кравцов|А.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'ПМ-23', 'subject' => 'Металообробне обладнання', 'teacher' => 'Пузирьов|О.Л.', 'lessonType' => 'laboratory', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-23', 'subject' => 'Металообробне обладнання', 'teacher' => 'Пузирьов|О.Л.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-23', 'subject' => 'Металообробне обладнання', 'teacher' => 'Пузирьов|О.Л.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-23', 'subject' => 'Правознавство', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-23', 'subject' => 'Правознавство', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-23', 'subject' => 'Проектування та виробництво заготовок', 'teacher' => 'Пузирьов|О.Л.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-23', 'subject' => 'Проектування та виробництво заготовок', 'teacher' => 'Пузирьов|О.Л.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'ПМ-23', 'subject' => 'Різання металів', 'teacher' => 'Пузирьов|О.Л.', 'lessonType' => 'lecture', 'requiredLessonCount' => 1],
            ['group' => 'ПМ-23', 'subject' => 'Різання металів (лб/л)', 'teacher' => 'Пузирьов|О.Л.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-23', 'subject' => 'Технологічні основи машинобудування', 'teacher' => 'Кравцов|А.В.', 'lessonType' => 'laboratory', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-23', 'subject' => 'Технологічні основи машинобудування', 'teacher' => 'Кравцов|А.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-23', 'subject' => 'Технологічні основи машинобудування', 'teacher' => 'Кравцов|А.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'ПМ-24', 'subject' => 'Базова військова підготовка', 'teacher' => 'Не визначено|Н.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'ПМ-24', 'subject' => 'Безпека життєдільності', 'teacher' => 'Кравцов|А.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 1],
            ['group' => 'ПМ-24', 'subject' => 'Безпека життєдільності', 'teacher' => 'Кравцов|А.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 1],
            ['group' => 'ПМ-24', 'subject' => 'Гідравліка', 'teacher' => 'Руденко|Т.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Гідравліка', 'teacher' => 'Руденко|Т.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Опір матеріалів', 'teacher' => 'Пирогов|В.В.', 'lessonType' => 'laboratory', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Опір матеріалів', 'teacher' => 'Пирогов|В.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Опір матеріалів', 'teacher' => 'Пирогов|В.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Опір матеріалів/ Теорія мех та машин', 'teacher' => 'Пирогов|В.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Правознавство', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Правознавство', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Теоретичні основи теплотехніки', 'teacher' => 'Руденко|Т.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Теоретичні основи теплотехніки', 'teacher' => 'Руденко|Т.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Теоретичні основи теплотехніки (с/л)', 'teacher' => 'Руденко|Т.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Теорія механізмів і машин', 'teacher' => 'Пирогов|В.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Теорія механізмів і машин', 'teacher' => 'Пирогов|В.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Філософія', 'teacher' => 'Ігнатьєв|В.А.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-24', 'subject' => 'Філософія', 'teacher' => 'Ігнатьєв|В.А.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-25', 'subject' => 'Іноземна мова', 'teacher' => 'Максимова|О.П.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'ПМ-25', 'subject' => 'Інформатика', 'teacher' => 'Сурков|К.Ю.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'ПМ-25', 'subject' => 'Історія та культура України', 'teacher' => 'Неборак|К.О.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-25', 'subject' => 'Історія та культура України', 'teacher' => 'Неборак|К.О.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-25', 'subject' => 'Вища математика', 'teacher' => 'Бондар|О.П.', 'lessonType' => 'lecture', 'requiredLessonCount' => 3],
            ['group' => 'ПМ-25', 'subject' => 'Вища математика', 'teacher' => 'Бондар|О.П.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-25', 'subject' => 'Основи здорового способу життя', 'teacher' => 'Гавриленко|М.І.', 'lessonType' => 'practical', 'requiredLessonCount' => 1],
            ['group' => 'ПМ-25', 'subject' => 'Основи робототехніки', 'teacher' => 'Мироненко|В.А.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-25', 'subject' => 'Українська мова за професійним спрямуванням', 'teacher' => 'Дідковська|Н.А.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-25', 'subject' => 'Фізика', 'teacher' => 'Буряк|Ю.В.', 'lessonType' => 'laboratory', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-25', 'subject' => 'Фізика', 'teacher' => 'Буряк|Ю.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-25', 'subject' => 'Фізика', 'teacher' => 'Буряк|Ю.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-25', 'subject' => 'Хімія', 'teacher' => 'Бохан|Ю.В.', 'lessonType' => 'laboratory', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-25', 'subject' => 'Хімія', 'teacher' => 'Бохан|Ю.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ПМ-25', 'subject' => 'Хімія', 'teacher' => 'Бохан|Ю.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 2],
            ['group' => 'ФН-22', 'subject' => 'Інвестування', 'teacher' => 'Василенко|І.М.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ФН-22', 'subject' => 'Інвестування', 'teacher' => 'Василенко|І.М.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ФН-22', 'subject' => 'Інформаційні системи у фінансах', 'teacher' => 'Штець|Т.Ф.', 'lessonType' => 'seminar', 'requiredLessonCount' => 4],
            ['group' => 'ФН-22', 'subject' => 'Аналіз банківської діяльності', 'teacher' => 'Фрунза|С.А.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ФН-22', 'subject' => 'Аналіз банківської діяльності', 'teacher' => 'Фрунза|С.А.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ФН-22', 'subject' => 'Бюджетна система', 'teacher' => 'Штець|Т.Ф.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ФН-22', 'subject' => 'Бюджетна система', 'teacher' => 'Штець|Т.Ф.', 'lessonType' => 'seminar', 'requiredLessonCount' => 1],
            ['group' => 'ФН-22', 'subject' => 'Страхування', 'teacher' => 'Фрунза|С.А.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ФН-22', 'subject' => 'Страхування', 'teacher' => 'Фрунза|С.А.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ФН-22', 'subject' => 'Фондовий ринок', 'teacher' => 'Василенко|І.М.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ФН-22', 'subject' => 'Фондовий ринок', 'teacher' => 'Василенко|І.М.', 'lessonType' => 'seminar', 'requiredLessonCount' => 1],
            ['group' => 'ФН-22', 'subject' => 'Фінансова діяльність суб\'єктів господарювання', 'teacher' => 'Загреба|І.Л.', 'lessonType' => 'lecture', 'requiredLessonCount' => 4],
            ['group' => 'ФН-23', 'subject' => 'Податкова система', 'teacher' => 'Гавриш|Г.О.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ФН-23', 'subject' => 'Податкова система', 'teacher' => 'Гавриш|Г.О.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ФН-23', 'subject' => 'Фінанси', 'teacher' => 'Фрунза|С.А.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ФН-23', 'subject' => 'Фінанси', 'teacher' => 'Фрунза|С.А.', 'lessonType' => 'practical', 'requiredLessonCount' => 1],
            ['group' => 'ФН-23', 'subject' => 'Фінанси', 'teacher' => 'Фрунза|С.А.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ФН-23', 'subject' => 'Фінансовий облік', 'teacher' => 'Гавриш|Г.О.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ФН-23', 'subject' => 'Фінансовий облік', 'teacher' => 'Гавриш|Г.О.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ФН-24', 'subject' => 'Іноземна мова', 'teacher' => 'Протасова|А.П.', 'lessonType' => 'practical', 'requiredLessonCount' => 6],
            ['group' => 'ФН-24', 'subject' => 'Іноземна мова', 'teacher' => 'Рожкова|Н.Г.', 'lessonType' => 'practical', 'requiredLessonCount' => 6],
            ['group' => 'ФН-24', 'subject' => 'Базова військова підготовка', 'teacher' => 'Не визначено|Н.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 3],
            ['group' => 'ФН-24', 'subject' => 'Міжнародна економіка', 'teacher' => 'Павлова|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ФН-24', 'subject' => 'Міжнародна економіка', 'teacher' => 'Павлова|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ФН-24', 'subject' => 'Мікроекономіка', 'teacher' => 'Павлова|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 6],
            ['group' => 'ФН-24', 'subject' => 'Мікроекономіка', 'teacher' => 'Павлова|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ФН-24', 'subject' => 'Психологія', 'teacher' => 'Нестеренко|Т.С.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ФН-24', 'subject' => 'Психологія', 'teacher' => 'Нестеренко|Т.С.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ФН-24', 'subject' => 'Статистика', 'teacher' => 'Мироненко|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ФН-24', 'subject' => 'Статистика', 'teacher' => 'Мироненко|О.В.', 'lessonType' => 'practical', 'requiredLessonCount' => 4],
            ['group' => 'ФН-25', 'subject' => 'Іноземна мова', 'teacher' => 'Протасова|А.П.', 'lessonType' => 'practical', 'requiredLessonCount' => 6],
            ['group' => 'ФН-25', 'subject' => 'Іноземна мова', 'teacher' => 'Рожкова|Н.Г.', 'lessonType' => 'practical', 'requiredLessonCount' => 6],
            ['group' => 'ФН-25', 'subject' => 'Інформатика', 'teacher' => 'Беспалий|В.І.', 'lessonType' => 'practical', 'requiredLessonCount' => 8],
            ['group' => 'ФН-25', 'subject' => 'Безпека життєдільності', 'teacher' => 'Кравцов|А.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ФН-25', 'subject' => 'Безпека життєдільності', 'teacher' => 'Кравцов|А.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ФН-25', 'subject' => 'Вища математика', 'teacher' => 'Мироненко|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 4],
            ['group' => 'ФН-25', 'subject' => 'Вища математика', 'teacher' => 'Мироненко|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ФН-25', 'subject' => 'Економічна теорія', 'teacher' => 'Павлова|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 4],
            ['group' => 'ФН-25', 'subject' => 'Економічна теорія', 'teacher' => 'Павлова|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ФН-25', 'subject' => 'Правознавство', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'lecture', 'requiredLessonCount' => 2],
            ['group' => 'ФН-25', 'subject' => 'Правознавство', 'teacher' => 'Щербина|О.В.', 'lessonType' => 'seminar', 'requiredLessonCount' => 2],
            ['group' => 'ФН-25', 'subject' => 'Українська мова за професійним спрямуванням', 'teacher' => 'Дідковська|Н.А.', 'lessonType' => 'seminar', 'requiredLessonCount' => 4],
        ];
    }
}
