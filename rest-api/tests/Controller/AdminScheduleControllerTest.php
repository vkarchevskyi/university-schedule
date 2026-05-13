<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Admin;
use App\Entity\ActionLog;
use App\Tests\Double\FakeScheduleGenerationPublisher;
use App\Tests\Double\FakeScheduleValidationClient;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminScheduleControllerTest extends WebTestCase
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
        FakeScheduleValidationClient::resetResult();
        FakeScheduleGenerationPublisher::reset();
    }

    public function testScheduleRoutesRequireAuthentication(): void
    {
        $this->client->jsonRequest('POST', '/api/admin/schedules', [
            'semesterId' => 1,
            'validFrom' => '2026-09-01',
            'validTo' => '2026-12-31',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanCreateUpdateAndDeleteDraftScheduleEntry(): void
    {
        $fixtures = $this->createScheduleFixtures();

        $schedule = $this->requestJson('POST', '/api/admin/schedules', [
            'semesterId' => $fixtures->semesterId,
            'validFrom' => '2026-09-01',
            'validTo' => '2026-12-31',
        ], 201);

        self::assertSame('draft', $this->stringValue($schedule, 'status'));
        self::assertSame([], $this->listValue($schedule, 'entries'));

        $entry = $this->requestJson('POST', sprintf('/api/admin/schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'teachingLoadIds' => [$fixtures->teachingLoadId],
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'lessonType' => 'laboratory',
            'roomId' => $fixtures->roomId,
            'timeSlotId' => $fixtures->timeSlotId,
            'dayOfWeek' => 1,
            'weekParity' => 'both',
            'groupIds' => [$fixtures->groupId],
        ], 201);

        self::assertSame('both', $this->stringValue($entry, 'weekParity'));

        $cards = $this->requestJson('GET', sprintf('/api/admin/schedules/%d/lesson-cards', $this->intValue($schedule, 'id')));
        $card = $this->objectAt($this->listValue($cards, 'items'), 0);

        self::assertSame(8, $this->intValue($card, 'requiredLessonCount'));
        self::assertSame(2, $this->intValue($card, 'scheduledLessonCount'));
        self::assertSame(6, $this->intValue($card, 'remainingLessonCount'));

        $updated = $this->requestJson('PATCH', sprintf('/api/admin/schedules/%d/entries/%d', $this->intValue($schedule, 'id'), $this->intValue($entry, 'id')), [
            'weekParity' => 'odd',
        ]);

        self::assertSame('odd', $this->stringValue($updated, 'weekParity'));

        $publicSchedule = $this->requestJson('GET', sprintf('/api/public/schedule?type=group&id=%d&weekStart=2026-09-07', $fixtures->groupId), authenticated: false);
        self::assertSame([], $this->listValue($publicSchedule, 'items'));

        $this->requestNoContent('DELETE', sprintf('/api/admin/schedules/%d/entries/%d', $this->intValue($schedule, 'id'), $this->intValue($entry, 'id')));

        $storedSchedule = $this->requestJson('GET', sprintf('/api/admin/schedules/%d', $this->intValue($schedule, 'id')));
        self::assertSame([], $this->listValue($storedSchedule, 'entries'));
    }

    public function testInvalidScheduleEntryPayloadReturnsValidationError(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $schedule = $this->requestJson('POST', '/api/admin/schedules', [
            'semesterId' => $fixtures->semesterId,
            'validFrom' => '2026-09-01',
            'validTo' => '2026-12-31',
        ], 201);

        $payload = $this->requestJson('POST', sprintf('/api/admin/schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'teachingLoadIds' => [],
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'lessonType' => 'laboratory',
            'roomId' => $fixtures->roomId,
            'timeSlotId' => $fixtures->timeSlotId,
            'dayOfWeek' => 9,
            'weekParity' => 'both',
            'groupIds' => [$fixtures->groupId],
        ], 422);

        self::assertArrayHasKey('dayOfWeek', $this->objectValue($payload, 'errors'));
    }

    public function testConflictingDraftEntryIsRejected(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $schedule = $this->requestJson('POST', '/api/admin/schedules', [
            'semesterId' => $fixtures->semesterId,
            'validFrom' => '2026-09-01',
            'validTo' => '2026-12-31',
        ], 201);

        $entryPayload = [
            'teachingLoadIds' => [$fixtures->teachingLoadId],
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'lessonType' => 'laboratory',
            'roomId' => $fixtures->roomId,
            'timeSlotId' => $fixtures->timeSlotId,
            'dayOfWeek' => 1,
            'weekParity' => 'odd',
            'groupIds' => [$fixtures->groupId],
        ];

        $this->requestJson('POST', sprintf('/api/admin/schedules/%d/entries', $this->intValue($schedule, 'id')), $entryPayload, 201);
        $conflict = $this->requestJson('POST', sprintf('/api/admin/schedules/%d/entries', $this->intValue($schedule, 'id')), [
            ...$entryPayload,
            'weekParity' => 'both',
        ], 422);

        self::assertArrayHasKey('teacherId', $this->objectValue($conflict, 'errors'));
    }

    public function testAdminCanValidateScheduleThroughGoValidationClient(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $schedule = $this->createDraftScheduleWithEntry($fixtures);
        FakeScheduleValidationClient::rejectResult('teacher_conflict', 'Teacher is already assigned at this time.', [1, 2]);

        $result = $this->requestJson('POST', sprintf('/api/admin/schedules/%d/validate', $this->intValue($schedule, 'id')));
        $conflict = $this->objectAt($this->listValue($result, 'conflicts'), 0);

        self::assertFalse($this->boolValue($result, 'valid'));
        self::assertSame('teacher_conflict', $this->stringValue($conflict, 'type'));
        self::assertArrayHasKey('scheduleId', FakeScheduleValidationClient::$payload ?? []);
    }

    public function testAdminCanPublishValidDraftSchedule(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $schedule = $this->createDraftScheduleWithEntry($fixtures);

        $published = $this->requestJson('POST', sprintf('/api/admin/schedules/%d/publish', $this->intValue($schedule, 'id')));

        self::assertSame('published', $this->stringValue($published, 'status'));
        self::assertNotNull($published['publishedAt'] ?? null);

        $publicSchedule = $this->requestJson('GET', sprintf('/api/public/schedule?type=group&id=%d&weekStart=2026-09-07', $fixtures->groupId), authenticated: false);
        self::assertCount(1, $this->listValue($publicSchedule, 'items'));

        $logs = $this->entityManager->getRepository(ActionLog::class)->findBy(['action' => 'schedule.published']);
        self::assertCount(1, $logs);
    }

    public function testInvalidScheduleCannotBePublished(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $schedule = $this->createDraftScheduleWithEntry($fixtures);
        FakeScheduleValidationClient::rejectResult('teaching_load_missing', 'Teaching load is incomplete.');

        $result = $this->requestJson('POST', sprintf('/api/admin/schedules/%d/publish', $this->intValue($schedule, 'id')), expectedStatus: 422);
        $conflict = $this->objectAt($this->listValue($result, 'conflicts'), 0);

        self::assertFalse($this->boolValue($result, 'valid'));
        self::assertSame('teaching_load_missing', $this->stringValue($conflict, 'type'));
    }

    public function testPublishedScheduleCannotBePublishedAgain(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $schedule = $this->createDraftScheduleWithEntry($fixtures);

        $this->requestJson('POST', sprintf('/api/admin/schedules/%d/publish', $this->intValue($schedule, 'id')));
        $result = $this->requestJson('POST', sprintf('/api/admin/schedules/%d/publish', $this->intValue($schedule, 'id')), expectedStatus: 422);

        self::assertArrayHasKey('status', $this->objectValue($result, 'errors'));
    }

    public function testAdminCanStartScheduleGenerationJob(): void
    {
        $fixtures = $this->createScheduleFixtures();

        $job = $this->requestJson('POST', '/api/admin/schedules/generate', [
            'semesterId' => $fixtures->semesterId,
        ], 202);

        self::assertSame('queued', $this->stringValue($job, 'status'));
        self::assertSame($fixtures->semesterId, $this->intValue($job, 'semesterId'));
        self::assertNull($job['generatedScheduleId'] ?? null);
        self::assertNotNull(FakeScheduleGenerationPublisher::$message);
        self::assertSame($this->stringValue($job, 'id'), FakeScheduleGenerationPublisher::$message['jobId']);
        self::assertSame($fixtures->semesterId, FakeScheduleGenerationPublisher::$message['semesterId']);

        $storedJob = $this->requestJson('GET', sprintf('/api/admin/generation-jobs/%s', $this->stringValue($job, 'id')));
        self::assertSame($this->stringValue($job, 'id'), $this->stringValue($storedJob, 'id'));
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function requestJson(string $method, string $uri, array $payload = [], int $expectedStatus = 200, bool $authenticated = true): array
    {
        $server = $authenticated ? ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $this->token)] : [];

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

    private function createScheduleFixtures(): AdminScheduleFixtures
    {
        $academicYear = $this->requestJson('POST', '/api/admin/academic-years', [
            'name' => '2026/2027',
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
            'name' => 'KN-22',
            'speciality' => 'Computer Science',
            'course' => 4,
            'studentCount' => 24,
        ], 201);
        $teacher = $this->requestJson('POST', '/api/admin/teachers', [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'department' => 'Computer Science',
        ], 201);
        $subject = $this->requestJson('POST', '/api/admin/subjects', [
            'name' => 'Programming',
        ], 201);
        $room = $this->requestJson('POST', '/api/admin/rooms', [
            'name' => 'Lab 1',
            'type' => 'computer',
            'capacity' => 30,
        ], 201);
        $timeSlot = $this->requestJson('POST', '/api/admin/time-slots', [
            'number' => 1,
            'startsAt' => '08:30:00',
            'endsAt' => '09:50:00',
        ], 201);
        $teachingLoad = $this->requestJson('POST', '/api/admin/teaching-loads', [
            'semesterId' => $this->intValue($semester, 'id'),
            'groupId' => $this->intValue($group, 'id'),
            'subjectId' => $this->intValue($subject, 'id'),
            'teacherId' => $this->intValue($teacher, 'id'),
            'lessonType' => 'laboratory',
            'requiredLessonCount' => 8,
        ], 201);

        return new AdminScheduleFixtures(
            $this->intValue($semester, 'id'),
            $this->intValue($group, 'id'),
            $this->intValue($teacher, 'id'),
            $this->intValue($subject, 'id'),
            $this->intValue($room, 'id'),
            $this->intValue($timeSlot, 'id'),
            $this->intValue($teachingLoad, 'id'),
        );
    }

    /** @return array<string, mixed> */
    private function createDraftScheduleWithEntry(AdminScheduleFixtures $fixtures): array
    {
        $schedule = $this->requestJson('POST', '/api/admin/schedules', [
            'semesterId' => $fixtures->semesterId,
            'validFrom' => '2026-09-01',
            'validTo' => '2026-12-31',
        ], 201);

        $this->requestJson('POST', sprintf('/api/admin/schedules/%d/entries', $this->intValue($schedule, 'id')), [
            'teachingLoadIds' => [$fixtures->teachingLoadId],
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'lessonType' => 'laboratory',
            'roomId' => $fixtures->roomId,
            'timeSlotId' => $fixtures->timeSlotId,
            'dayOfWeek' => 1,
            'weekParity' => 'both',
            'groupIds' => [$fixtures->groupId],
        ], 201);

        return $schedule;
    }

    private function login(): string
    {
        $passwordHash = password_hash('correct-password', PASSWORD_BCRYPT);

        $admin = new Admin('Ada', 'Lovelace', 'admin@example.com', $passwordHash, new \DateTimeImmutable());
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

final readonly class AdminScheduleFixtures
{
    public function __construct(
        public int $semesterId,
        public int $groupId,
        public int $teacherId,
        public int $subjectId,
        public int $roomId,
        public int $timeSlotId,
        public int $teachingLoadId,
    ) {}
}
