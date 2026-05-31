<?php

declare(strict_types=1);

namespace App\Tests\Domain\Schedule;

use App\Domain\Schedule\ScheduleEntryConflictPolicy;
use App\Entity\AcademicYear;
use App\Entity\User;
use App\Enum\UserRole;
use App\Entity\Group as StudentGroup;
use App\Entity\Room;
use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use App\Entity\ScheduleEntryGroup;
use App\Entity\Semester;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\TimeSlot;
use App\Enum\LessonType;
use App\Enum\RoomType;
use App\Enum\ScheduleStatus;
use App\Enum\WeekParity;
use App\Service\ScheduleEntry\ScheduleEntryData;
use PHPUnit\Framework\TestCase;

final class ScheduleEntryConflictPolicyTest extends TestCase
{
    private ScheduleEntryConflictPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new ScheduleEntryConflictPolicy();
    }

    public function testDetectsTeacherConflictAtSameTimeAndOverlappingParity(): void
    {
        $fixtures = $this->fixtures(WeekParity::Odd);

        $conflict = $this->policy->conflict($fixtures->schedule, new ScheduleEntryData(
            $fixtures->subject,
            $fixtures->teacher,
            LessonType::Laboratory,
            new Room('Lab 2', RoomType::Computer, 20),
            $fixtures->timeSlot,
            1,
            WeekParity::Both,
            [$fixtures->group],
            [],
        ));

        self::assertNotNull($conflict);
        self::assertSame('teacherId', $conflict->field);
    }

    public function testDetectsGroupConflictAtSameTimeAndOverlappingParity(): void
    {
        $fixtures = $this->fixtures(WeekParity::Both);

        $conflict = $this->policy->conflict($fixtures->schedule, new ScheduleEntryData(
            $fixtures->subject,
            new Teacher('Grace', 'Hopper', 'Computer Science'),
            LessonType::Laboratory,
            new Room('Lab 2', RoomType::Computer, 20),
            $fixtures->timeSlot,
            1,
            WeekParity::Even,
            [$fixtures->group],
            [],
        ));

        self::assertNotNull($conflict);
        self::assertSame('groupIds', $conflict->field);
    }

    public function testDifferentWeekParityDoesNotConflict(): void
    {
        $fixtures = $this->fixtures(WeekParity::Odd);

        $conflict = $this->policy->conflict($fixtures->schedule, new ScheduleEntryData(
            $fixtures->subject,
            $fixtures->teacher,
            LessonType::Laboratory,
            $fixtures->room,
            $fixtures->timeSlot,
            1,
            WeekParity::Even,
            [$fixtures->group],
            [],
        ));

        self::assertNull($conflict);
    }

    public function testIgnoredEntryDoesNotConflictWithItself(): void
    {
        $fixtures = $this->fixtures(WeekParity::Both);

        $conflict = $this->policy->conflict($fixtures->schedule, new ScheduleEntryData(
            $fixtures->subject,
            $fixtures->teacher,
            LessonType::Laboratory,
            $fixtures->room,
            $fixtures->timeSlot,
            1,
            WeekParity::Both,
            [$fixtures->group],
            [],
        ), $fixtures->entry);

        self::assertNull($conflict);
    }

    private function fixtures(WeekParity $weekParity): ScheduleEntryConflictFixtures
    {
        $admin = new User('Ada', 'Lovelace', 'admin@example.com', 'hash', new \DateTimeImmutable('2026-01-01'), UserRole::Admin);
        $academicYear = new AcademicYear('2026/2027', new \DateTimeImmutable('2026-09-01'), new \DateTimeImmutable('2027-06-30'));
        $semester = new Semester($academicYear, 1, new \DateTimeImmutable('2026-09-07'), new \DateTimeImmutable('2026-12-31'), WeekParity::Odd);
        $schedule = new Schedule($semester, ScheduleStatus::Draft, new \DateTimeImmutable('2026-09-07'), new \DateTimeImmutable('2026-12-31'), $admin, new \DateTimeImmutable('2026-08-01'));
        $subject = new Subject('Programming');
        $teacher = new Teacher('John', 'Doe', 'Computer Science');
        $room = new Room('Lab 1', RoomType::Computer, 30);
        $timeSlot = new TimeSlot(1, new \DateTimeImmutable('08:30'), new \DateTimeImmutable('10:00'));
        $group = new StudentGroup('KN-22', 'Computer Science', 4, 24);
        $this->setEntityId($group, 10);

        $entry = new ScheduleEntry($schedule, $subject, $teacher, LessonType::Laboratory, $room, $timeSlot, 1, $weekParity);
        $schedule->addEntry($entry);
        $entry->addGroup(new ScheduleEntryGroup($entry, $group));

        return new ScheduleEntryConflictFixtures($schedule, $entry, $subject, $teacher, $room, $timeSlot, $group);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }
}

final readonly class ScheduleEntryConflictFixtures
{
    public function __construct(
        public Schedule $schedule,
        public ScheduleEntry $entry,
        public Subject $subject,
        public Teacher $teacher,
        public Room $room,
        public TimeSlot $timeSlot,
        public StudentGroup $group,
    ) {}
}
