<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AcademicYear;
use App\Entity\User;
use App\Enum\UserRole;
use App\Entity\Group as StudentGroup;
use App\Entity\Lesson;
use App\Entity\LessonGroup;
use App\Entity\Room;
use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use App\Entity\ScheduleEntryGroup;
use App\Entity\Semester;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\TimeSlot;
use App\Enum\LessonType;
use App\Enum\ScheduleStatus;
use App\Enum\WeekParity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PublicScheduleControllerTest extends WebTestCase
{
    use JsonTestAssertions;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testPublicLookupsDoNotRequireAuthentication(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures();

        $groups = $this->requestJson('/api/public/groups');
        $teachers = $this->requestJson('/api/public/teachers');
        $rooms = $this->requestJson('/api/public/rooms');

        self::assertSame($fixtures->group->getId(), $this->intValue($this->objectAt($this->listValue($groups, 'items'), 0), 'id'));
        self::assertSame($fixtures->teacher->getId(), $this->intValue($this->objectAt($this->listValue($teachers, 'items'), 0), 'id'));
        self::assertSame($fixtures->room->getId(), $this->intValue($this->objectAt($this->listValue($rooms, 'items'), 0), 'id'));
    }

    public function testScheduleCanBeFilteredByGroupTeacherAndRoom(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures();

        $groupSchedule = $this->requestJson(sprintf('/api/public/schedule?type=group&id=%d&weekStart=2026-09-07', $fixtures->group->getId()));
        $teacherSchedule = $this->requestJson(sprintf('/api/public/schedule?type=teacher&id=%d&weekStart=2026-09-07', $fixtures->teacher->getId()));
        $roomSchedule = $this->requestJson(sprintf('/api/public/schedule?type=room&id=%d&weekStart=2026-09-07', $fixtures->room->getId()));

        foreach ([$groupSchedule, $teacherSchedule, $roomSchedule] as $schedule) {
            self::assertSame('2026-09-07', $this->stringValue($schedule, 'weekStart'));
            $items = $this->listValue($schedule, 'items');
            $item = $this->objectAt($items, 0);
            $subject = $this->objectValue($item, 'subject');

            self::assertCount(1, $items);
            self::assertSame('Programming', $this->stringValue($subject, 'name'));
            self::assertSame('laboratory', $this->stringValue($item, 'lessonType'));
            self::assertFalse($this->boolValue($item, 'isCancelled'));
            self::assertFalse($this->boolValue($item, 'isOverride'));
        }
    }

    public function testDraftScheduleIsHiddenFromPublicSchedule(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures(ScheduleStatus::Draft);

        $schedule = $this->requestJson(sprintf('/api/public/schedule?type=group&id=%d&weekStart=2026-09-07', $fixtures->group->getId()));

        self::assertSame([], $this->listValue($schedule, 'items'));
    }

    public function testWeekParityIsApplied(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures(weekParity: WeekParity::Odd);

        $oddWeek = $this->requestJson(sprintf('/api/public/schedule?type=group&id=%d&weekStart=2026-09-07', $fixtures->group->getId()));
        $evenWeek = $this->requestJson(sprintf('/api/public/schedule?type=group&id=%d&weekStart=2026-09-14', $fixtures->group->getId()));

        self::assertCount(1, $this->listValue($oddWeek, 'items'));
        self::assertSame([], $this->listValue($evenWeek, 'items'));
    }

    public function testLessonOverrideAndCancellationFlagsAreReturned(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures();
        $overrideRoom = new Room('Lab 2', 'computer', 20);
        $overrideLesson = new Lesson(
            new \DateTimeImmutable('2026-09-07'),
            $fixtures->subject,
            $fixtures->teacher,
            LessonType::Laboratory,
            $overrideRoom,
            $fixtures->timeSlot,
            true,
            true,
            $fixtures->entry,
        );
        $lessonGroup = new LessonGroup($overrideLesson, $fixtures->group);
        $overrideLesson->addGroup($lessonGroup);

        $this->entityManager->persist($overrideRoom);
        $this->entityManager->persist($overrideLesson);
        $this->entityManager->persist($lessonGroup);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $schedule = $this->requestJson(sprintf('/api/public/schedule?type=group&id=%d&weekStart=2026-09-07', $fixtures->group->getId()));

        $item = $this->objectAt($this->listValue($schedule, 'items'), 0);
        $room = $this->objectValue($item, 'room');

        self::assertTrue($this->boolValue($item, 'isCancelled'));
        self::assertTrue($this->boolValue($item, 'isOverride'));
        self::assertSame('Lab 2', $this->stringValue($room, 'name'));
    }

    public function testInvalidScheduleQueryReturnsValidationErrors(): void
    {
        $payload = $this->requestJson('/api/public/schedule?type=student&id=0&weekStart=2026-09-08', 422);

        $errors = $this->objectValue($payload, 'errors');

        self::assertSame(422, $this->intValue($payload, 'status'));
        self::assertArrayHasKey('type', $errors);
        self::assertArrayHasKey('id', $errors);
        self::assertArrayHasKey('weekStart', $errors);
    }

    public function testUnknownFilterTargetReturnsNotFound(): void
    {
        $payload = $this->requestJson('/api/public/schedule?type=group&id=999&weekStart=2026-09-07', 404);

        self::assertSame('Filter target not found.', $this->stringValue($payload, 'error'));
    }

    /** @return array<string, mixed> */
    private function requestJson(string $uri, int $expectedStatus = 200): array
    {
        $this->client->request('GET', $uri);

        self::assertResponseStatusCodeSame($expectedStatus);

        return $this->responseJson($this->client);
    }

    private function createPublishedScheduleFixtures(
        ScheduleStatus $status = ScheduleStatus::Published,
        WeekParity $weekParity = WeekParity::Both,
    ): PublicScheduleFixtures {
        $admin = new User('Ada', 'Lovelace', 'admin@example.com', 'hash', new \DateTimeImmutable('2026-01-01'), UserRole::Admin);
        $academicYear = new AcademicYear('2026/2027', new \DateTimeImmutable('2026-09-01'), new \DateTimeImmutable('2027-06-30'));
        $semester = new Semester($academicYear, 1, new \DateTimeImmutable('2026-09-07'), new \DateTimeImmutable('2026-12-31'), WeekParity::Odd);
        $group = new StudentGroup('KN-22', 'Computer Science', 4, 24);
        $teacher = new Teacher('John', 'Doe', 'Computer Science');
        $subject = new Subject('Programming');
        $room = new Room('Lab 1', 'computer', 30);
        $timeSlot = new TimeSlot(1, new \DateTimeImmutable('08:30'), new \DateTimeImmutable('10:00'));
        $schedule = new Schedule(
            $semester,
            $status,
            new \DateTimeImmutable('2026-09-07'),
            new \DateTimeImmutable('2026-12-31'),
            $admin,
            new \DateTimeImmutable('2026-08-20T10:00:00+00:00'),
            $status === ScheduleStatus::Published ? new \DateTimeImmutable('2026-08-21T10:00:00+00:00') : null,
        );
        $entry = new ScheduleEntry($schedule, $subject, $teacher, LessonType::Laboratory, $room, $timeSlot, 1, $weekParity);
        $schedule->addEntry($entry);
        $entryGroup = new ScheduleEntryGroup($entry, $group);
        $entry->addGroup($entryGroup);

        foreach ([$admin, $academicYear, $semester, $group, $teacher, $subject, $room, $timeSlot, $schedule, $entry, $entryGroup] as $entity) {
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();

        return new PublicScheduleFixtures($group, $teacher, $subject, $room, $timeSlot, $entry);
    }
}

final readonly class PublicScheduleFixtures
{
    public function __construct(
        public StudentGroup $group,
        public Teacher $teacher,
        public Subject $subject,
        public Room $room,
        public TimeSlot $timeSlot,
        public ScheduleEntry $entry,
    ) {}
}
