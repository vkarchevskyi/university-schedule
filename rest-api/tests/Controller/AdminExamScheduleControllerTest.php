<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Enum\UserRole;
use App\Tests\Double\FakeExamScheduleGenerationPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminExamScheduleControllerTest extends WebTestCase
{
    use JsonTestAssertions;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private string $token;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $this->token = $this->login();
        FakeExamScheduleGenerationPublisher::reset();
    }

    public function testExamScheduleRoutesRequireAuthentication(): void
    {
        $this->client->jsonRequest('POST', '/api/admin/exam-schedules', [
            'semesterId' => 1,
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanCreateUpdateValidateAndDeleteExamScheduleEntries(): void
    {
        $fixtures = $this->createFixtures();
        $schedule = $this->requestJson('POST', '/api/admin/exam-schedules', [
            'semesterId' => $fixtures->semesterId,
        ], 201);

        self::assertSame('draft', $this->stringValue($schedule, 'status'));
        self::assertSame([], $this->listValue($schedule, 'entries'));

        $list = $this->requestJson('GET', sprintf('/api/admin/exam-schedules?semesterId=%d', $fixtures->semesterId));
        self::assertCount(1, $this->listValue($list, 'items'));

        $consultation = $this->requestJson('POST', sprintf('/api/admin/exam-schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'type' => 'consultation',
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'roomId' => $fixtures->roomId,
            'groupIds' => [$fixtures->groupId],
            'entryDate' => '2026-12-20',
            'startsAt' => '09:00:00',
        ], 201);

        self::assertSame('consultation', $this->stringValue($consultation, 'type'));

        $exam = $this->requestJson('POST', sprintf('/api/admin/exam-schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'type' => 'exam',
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'roomId' => $fixtures->roomId,
            'groupIds' => [$fixtures->groupId],
            'entryDate' => '2026-12-21',
            'startsAt' => '09:00:00',
        ], 201);

        $updated = $this->requestJson('PATCH', sprintf('/api/admin/exam-schedules/%d/entries/%d', $this->intValue($schedule, 'id'), $this->intValue($exam, 'id')), [
            'startsAt' => '10:00:00',
        ]);
        self::assertSame('10:00:00', $this->stringValue($updated, 'startsAt'));

        $valid = $this->requestJson('POST', sprintf('/api/admin/exam-schedules/%d/validate', $this->intValue($schedule, 'id')));
        self::assertTrue($this->boolValue($valid, 'valid'));

        $stored = $this->requestJson('GET', sprintf('/api/admin/exam-schedules/%d', $this->intValue($schedule, 'id')));
        self::assertCount(2, $this->listValue($stored, 'entries'));

        $this->requestNoContent('DELETE', sprintf('/api/admin/exam-schedules/%d/entries/%d', $this->intValue($schedule, 'id'), $this->intValue($exam, 'id')));
        $afterDelete = $this->requestJson('GET', sprintf('/api/admin/exam-schedules/%d', $this->intValue($schedule, 'id')));
        self::assertCount(1, $this->listValue($afterDelete, 'entries'));

        $this->requestNoContent('DELETE', sprintf('/api/admin/exam-schedules/%d', $this->intValue($schedule, 'id')));
        $this->requestJson('GET', sprintf('/api/admin/exam-schedules/%d', $this->intValue($schedule, 'id')), expectedStatus: 404);
    }

    public function testAdminCanRequestExamScheduleGenerationAndReadJob(): void
    {
        $fixtures = $this->createFixtures();

        $job = $this->requestJson('POST', '/api/admin/exam-schedules/generate', [
            'semesterId' => $fixtures->semesterId,
        ], 202);

        self::assertSame($fixtures->semesterId, $this->intValue($job, 'semesterId'));
        self::assertSame('queued', $this->stringValue($job, 'status'));
        self::assertNull($job['generatedExamScheduleId']);

        self::assertNotNull(FakeExamScheduleGenerationPublisher::$message);
        self::assertSame($this->stringValue($job, 'id'), FakeExamScheduleGenerationPublisher::$message['jobId']);
        self::assertSame($fixtures->semesterId, FakeExamScheduleGenerationPublisher::$message['semesterId']);

        $stored = $this->requestJson('GET', sprintf('/api/admin/exam-schedule-generation-jobs/%s', $this->stringValue($job, 'id')));

        self::assertSame($this->stringValue($job, 'id'), $this->stringValue($stored, 'id'));
        self::assertSame('queued', $this->stringValue($stored, 'status'));
    }

    public function testExamWithoutMatchingConsultationIsRejected(): void
    {
        $fixtures = $this->createFixtures();
        $schedule = $this->requestJson('POST', '/api/admin/exam-schedules', [
            'semesterId' => $fixtures->semesterId,
        ], 201);

        $result = $this->requestJson('POST', sprintf('/api/admin/exam-schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'type' => 'exam',
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'roomId' => $fixtures->roomId,
            'groupIds' => [$fixtures->groupId],
            'entryDate' => '2026-12-21',
            'startsAt' => '09:00:00',
        ], 422);

        $conflict = $this->objectAt($this->listValue($result, 'conflicts'), 0);
        self::assertSame('consultation_missing', $this->stringValue($conflict, 'type'));
    }

    public function testExamConsultationCanUseDifferentRoomFromExam(): void
    {
        $fixtures = $this->createFixtures();
        $examRoom = $this->requestJson('POST', '/api/admin/rooms', [
            'name' => 'Assembly Hall',
            'type' => 'lecture',
            'capacity' => 120,
        ], 201);
        $schedule = $this->requestJson('POST', '/api/admin/exam-schedules', [
            'semesterId' => $fixtures->semesterId,
        ], 201);

        $this->requestJson('POST', sprintf('/api/admin/exam-schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'type' => 'consultation',
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'roomId' => $fixtures->roomId,
            'groupIds' => [$fixtures->groupId],
            'entryDate' => '2026-12-20',
            'startsAt' => '09:00:00',
        ], 201);

        $exam = $this->requestJson('POST', sprintf('/api/admin/exam-schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'type' => 'exam',
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'roomId' => $this->intValue($examRoom, 'id'),
            'groupIds' => [$fixtures->groupId],
            'entryDate' => '2026-12-21',
            'startsAt' => '09:00:00',
        ], 201);

        self::assertSame($this->intValue($examRoom, 'id'), $this->intValue($exam, 'roomId'));
    }

    public function testExamEntryConflictIsRejected(): void
    {
        $fixtures = $this->createFixtures();
        $schedule = $this->createValidExamSchedule($fixtures, '2026-12-20', '2026-12-21');

        $result = $this->requestJson('POST', sprintf('/api/admin/exam-schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'type' => 'consultation',
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'roomId' => $fixtures->roomId,
            'groupIds' => [$fixtures->groupId],
            'entryDate' => '2026-12-21',
            'startsAt' => '09:00:00',
        ], 422);

        $conflict = $this->objectAt($this->listValue($result, 'conflicts'), 0);
        self::assertSame('teacher_conflict', $this->stringValue($conflict, 'type'));
    }

    public function testRoomCapacityIsValidated(): void
    {
        $fixtures = $this->createFixtures(roomCapacity: 10);
        $schedule = $this->requestJson('POST', '/api/admin/exam-schedules', [
            'semesterId' => $fixtures->semesterId,
        ], 201);

        $result = $this->requestJson('POST', sprintf('/api/admin/exam-schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'type' => 'consultation',
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'roomId' => $fixtures->roomId,
            'groupIds' => [$fixtures->groupId],
            'entryDate' => '2026-12-20',
            'startsAt' => '09:00:00',
        ], 422);

        $conflict = $this->objectAt($this->listValue($result, 'conflicts'), 0);
        self::assertSame('room_capacity_conflict', $this->stringValue($conflict, 'type'));
    }

    public function testGroupExamMinimumIntervalIsValidated(): void
    {
        $fixtures = $this->createFixtures();
        $schedule = $this->createValidExamSchedule($fixtures, '2026-12-20', '2026-12-21');
        $second = $this->createSecondSubject();

        $this->requestJson('POST', sprintf('/api/admin/teacher-subjects'), [
            'teacherId' => $fixtures->teacherId,
            'subjectId' => $this->intValue($second, 'id'),
        ], 201);
        $this->requestJson('POST', sprintf('/api/admin/exam-schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'type' => 'consultation',
            'subjectId' => $this->intValue($second, 'id'),
            'teacherId' => $fixtures->teacherId,
            'roomId' => $fixtures->roomId,
            'groupIds' => [$fixtures->groupId],
            'entryDate' => '2026-12-20',
            'startsAt' => '11:00:00',
        ], 201);

        $result = $this->requestJson('POST', sprintf('/api/admin/exam-schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'type' => 'exam',
            'subjectId' => $this->intValue($second, 'id'),
            'teacherId' => $fixtures->teacherId,
            'roomId' => $fixtures->roomId,
            'groupIds' => [$fixtures->groupId],
            'entryDate' => '2026-12-21',
            'startsAt' => '11:00:00',
        ], 422);

        $conflict = $this->objectAt($this->listValue($result, 'conflicts'), 0);
        self::assertSame('group_exam_interval_conflict', $this->stringValue($conflict, 'type'));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function requestJson(string $method, string $uri, array $payload = [], int $expectedStatus = 200): array
    {
        $server = ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $this->token)];

        if ($method === 'GET' || $method === 'DELETE') {
            $this->client->request($method, $uri, server: $server);
        } else {
            $this->client->jsonRequest($method, $uri, $payload, server: $server);
        }

        self::assertResponseStatusCodeSame($expectedStatus);

        return $this->responseJson($this->client);
    }

    private function requestNoContent(string $method, string $uri): void
    {
        $this->client->request($method, $uri, server: ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $this->token)]);

        self::assertResponseStatusCodeSame(204);
    }

    private function createFixtures(int $roomCapacity = 30): AdminExamScheduleFixtures
    {
        $academicYear = $this->requestJson('POST', '/api/admin/academic-years', [
            'name' => sprintf('2026/2027-%d', $roomCapacity),
            'startsAt' => '2026-09-01',
            'endsAt' => '2027-06-30',
        ], 201);
        $semester = $this->requestJson('POST', '/api/admin/semesters', [
            'academicYearId' => $this->intValue($academicYear, 'id'),
            'number' => 1,
            'startsAt' => '2026-09-01',
            'endsAt' => '2026-12-31',
            'firstWeekParity' => 'odd',
        ], 201);
        $group = $this->requestJson('POST', '/api/admin/groups', [
            'name' => sprintf('KN-%d', $roomCapacity),
            'speciality' => 'Computer Science',
            'course' => 4,
            'studentCount' => 24,
        ], 201);
        $teacher = $this->requestJson('POST', '/api/admin/teachers', [
            'firstName' => 'John',
            'lastName' => sprintf('Doe-%d', $roomCapacity),
            'department' => 'Computer Science',
        ], 201);
        $subject = $this->requestJson('POST', '/api/admin/subjects', [
            'name' => sprintf('Programming-%d', $roomCapacity),
        ], 201);
        $room = $this->requestJson('POST', '/api/admin/rooms', [
            'name' => sprintf('Room-%d', $roomCapacity),
            'type' => 'computer',
            'capacity' => $roomCapacity,
        ], 201);

        $this->requestJson('POST', '/api/admin/teacher-subjects', [
            'teacherId' => $this->intValue($teacher, 'id'),
            'subjectId' => $this->intValue($subject, 'id'),
        ], 201);

        return new AdminExamScheduleFixtures(
            $this->intValue($semester, 'id'),
            $this->intValue($group, 'id'),
            $this->intValue($teacher, 'id'),
            $this->intValue($subject, 'id'),
            $this->intValue($room, 'id'),
        );
    }

    /** @return array<string, mixed> */
    private function createValidExamSchedule(AdminExamScheduleFixtures $fixtures, string $consultationDate, string $examDate): array
    {
        $schedule = $this->requestJson('POST', '/api/admin/exam-schedules', [
            'semesterId' => $fixtures->semesterId,
        ], 201);
        $this->requestJson('POST', sprintf('/api/admin/exam-schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'type' => 'consultation',
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'roomId' => $fixtures->roomId,
            'groupIds' => [$fixtures->groupId],
            'entryDate' => $consultationDate,
            'startsAt' => '09:00:00',
        ], 201);
        $this->requestJson('POST', sprintf('/api/admin/exam-schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'type' => 'exam',
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'roomId' => $fixtures->roomId,
            'groupIds' => [$fixtures->groupId],
            'entryDate' => $examDate,
            'startsAt' => '09:00:00',
        ], 201);

        return $schedule;
    }

    /** @return array<string, mixed> */
    private function createSecondSubject(): array
    {
        return $this->requestJson('POST', '/api/admin/subjects', [
            'name' => 'Math',
        ], 201);
    }

    private function login(): string
    {
        $passwordHash = password_hash('correct-password', PASSWORD_BCRYPT);

        $admin = new User('Ada', 'Lovelace', 'admin@example.com', $passwordHash, new \DateTimeImmutable(), UserRole::Admin);
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $this->client->jsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        self::assertResponseIsSuccessful();

        $payload = $this->responseJson($this->client);

        return $this->stringValue($payload, 'token');
    }
}

final readonly class AdminExamScheduleFixtures
{
    public function __construct(
        public int $semesterId,
        public int $groupId,
        public int $teacherId,
        public int $subjectId,
        public int $roomId,
    ) {}
}
