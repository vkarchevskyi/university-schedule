<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Schedule;
use App\Entity\User;
use App\Enum\ScheduleStatus;
use App\Enum\UserRole;
use App\Entity\ActionLog;
use App\Tests\Double\FakeScheduleGenerationPublisher;
use App\Tests\Double\FakeScheduleValidationClient;
use App\Tests\Double\FakeTelegramNotificationPublisher;
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
        FakeTelegramNotificationPublisher::reset();
    }

    public function testScheduleRoutesRequireAuthentication(): void
    {
        $this->client->request('GET', '/api/admin/schedules');

        self::assertResponseStatusCodeSame(401);

        $this->client->request('GET', '/api/admin/action-logs');

        self::assertResponseStatusCodeSame(401);

        $this->client->request('GET', '/api/admin/generation-jobs');

        self::assertResponseStatusCodeSame(401);

        $this->client->jsonRequest('POST', '/api/admin/notifications/ws-ticket');

        self::assertResponseStatusCodeSame(401);

        $this->client->jsonRequest('POST', '/api/admin/schedules', [
            'semesterId' => 1,
            'validFrom' => '2026-09-01',
            'validTo' => '2026-12-31',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testAdminCanCreateWebSocketTicket(): void
    {
        $ticket = $this->requestJson('POST', '/api/admin/notifications/ws-ticket');

        self::assertNotSame('', $this->stringValue($ticket, 'ticket'));
        self::assertNotSame('', $this->stringValue($ticket, 'expiresAt'));
    }

    public function testAdminCanListSchedulesWithOptionalSemesterFilter(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $first = $this->requestJson('POST', '/api/admin/schedules', [
            'semesterId' => $fixtures->semesterId,
            'validFrom' => '2026-09-01',
            'validTo' => '2026-12-31',
        ], 201);
        $second = $this->requestJson('POST', '/api/admin/schedules', [
            'semesterId' => $fixtures->semesterId,
            'validFrom' => '2026-09-15',
            'validTo' => '2026-12-31',
        ], 201);

        $list = $this->requestJson('GET', '/api/admin/schedules');
        $items = $this->listValue($list, 'items');

        self::assertCount(2, $items);
        self::assertSame($this->intValue($second, 'id'), $this->intValue($this->objectAt($items, 0), 'id'));

        $filtered = $this->requestJson('GET', sprintf('/api/admin/schedules?semesterId=%d', $fixtures->semesterId));

        self::assertCount(2, $this->listValue($filtered, 'items'));
        self::assertNotSame($this->intValue($first, 'id'), $this->intValue($second, 'id'));
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
        $createdScheduleLog = $this->actionLog('schedule.created');
        self::assertSame('schedule', $createdScheduleLog->getEntityType());
        self::assertSame($this->intValue($schedule, 'id'), $createdScheduleLog->getEntityId());
        self::assertNull($createdScheduleLog->getBeforePayload());
        self::assertSame('draft', $createdScheduleLog->getAfterPayload()['status'] ?? null);

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
        $createdEntryLog = $this->actionLog('schedule.entry.created');
        self::assertSame('schedule_entry', $createdEntryLog->getEntityType());
        self::assertSame($this->intValue($entry, 'id'), $createdEntryLog->getEntityId());
        self::assertNull($createdEntryLog->getBeforePayload());
        self::assertSame([$fixtures->groupId], $createdEntryLog->getAfterPayload()['groupIds'] ?? null);

        $cards = $this->requestJson('GET', sprintf('/api/admin/schedules/%d/lesson-cards', $this->intValue($schedule, 'id')));
        $card = $this->objectAt($this->listValue($cards, 'items'), 0);

        self::assertSame(8, $this->intValue($card, 'requiredLessonCount'));
        self::assertSame(2, $this->intValue($card, 'scheduledLessonCount'));
        self::assertSame(6, $this->intValue($card, 'remainingLessonCount'));

        $updated = $this->requestJson('PATCH', sprintf('/api/admin/schedules/%d/entries/%d', $this->intValue($schedule, 'id'), $this->intValue($entry, 'id')), [
            'teachingLoadIds' => [$fixtures->teachingLoadId],
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'lessonType' => 'laboratory',
            'roomId' => $fixtures->roomId,
            'timeSlotId' => $fixtures->timeSlotId,
            'dayOfWeek' => 1,
            'weekParity' => 'odd',
            'groupIds' => [$fixtures->groupId],
        ]);

        self::assertSame('odd', $this->stringValue($updated, 'weekParity'));
        $updatedEntryLog = $this->actionLog('schedule.entry.updated');
        self::assertSame('both', $updatedEntryLog->getBeforePayload()['weekParity'] ?? null);
        self::assertSame('odd', $updatedEntryLog->getAfterPayload()['weekParity'] ?? null);

        $publicSchedule = $this->requestJson('GET', sprintf('/api/public/schedule?type=group&id=%d&weekStart=2026-09-07', $fixtures->groupId), authenticated: false);
        self::assertSame([], $this->listValue($publicSchedule, 'items'));

        $this->requestNoContent('DELETE', sprintf('/api/admin/schedules/%d/entries/%d', $this->intValue($schedule, 'id'), $this->intValue($entry, 'id')));

        $storedSchedule = $this->requestJson('GET', sprintf('/api/admin/schedules/%d', $this->intValue($schedule, 'id')));
        self::assertSame([], $this->listValue($storedSchedule, 'entries'));
        $deletedEntryLog = $this->actionLog('schedule.entry.deleted');
        self::assertSame('odd', $deletedEntryLog->getBeforePayload()['weekParity'] ?? null);
        self::assertNull($deletedEntryLog->getAfterPayload());
    }

    public function testAdminCanListActionLogs(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $schedule = $this->requestJson('POST', '/api/admin/schedules', [
            'semesterId' => $fixtures->semesterId,
            'validFrom' => '2026-09-01',
            'validTo' => '2026-12-31',
        ], 201);

        $logs = $this->requestJson('GET', '/api/admin/action-logs');
        $log = $this->objectAt($this->listValue($logs, 'items'), 0);
        $user = $this->objectValue($log, 'user');
        $afterPayload = $this->objectValue($log, 'afterPayload');

        self::assertSame('schedule.created', $this->stringValue($log, 'action'));
        self::assertSame($this->intValue($schedule, 'id'), $this->intValue($log, 'entityId'));
        self::assertSame('Ada', $this->stringValue($user, 'firstName'));
        self::assertSame('admin@example.com', $this->stringValue($user, 'email'));
        self::assertSame('draft', $this->stringValue($afterPayload, 'status'));
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
            'teachingLoadIds' => [$fixtures->teachingLoadId],
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'lessonType' => 'laboratory',
            'roomId' => $fixtures->roomId,
            'timeSlotId' => $fixtures->timeSlotId,
            'dayOfWeek' => 6,
            'weekParity' => 'both',
            'groupIds' => [$fixtures->groupId],
        ], 422);

        self::assertArrayHasKey('dayOfWeek', $this->objectValue($payload, 'errors'));
    }

    public function testComputerRoomRequiredTeachingLoadRejectsLectureRoom(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $lectureRoom = $this->requestJson('POST', '/api/admin/rooms', [
            'name' => 'Lecture 1',
            'type' => 'lecture',
            'capacity' => 30,
        ], 201);
        $this->requestJson('PATCH', '/api/admin/teaching-loads/' . $fixtures->teachingLoadId, [
            'requiresComputerRoom' => true,
        ]);
        $schedule = $this->requestJson('POST', '/api/admin/schedules', [
            'semesterId' => $fixtures->semesterId,
            'validFrom' => '2026-09-01',
            'validTo' => '2026-12-31',
        ], 201);

        $payload = [
            'teachingLoadIds' => [$fixtures->teachingLoadId],
            'subjectId' => $fixtures->subjectId,
            'teacherId' => $fixtures->teacherId,
            'lessonType' => 'laboratory',
            'roomId' => $this->intValue($lectureRoom, 'id'),
            'timeSlotId' => $fixtures->timeSlotId,
            'dayOfWeek' => 1,
            'weekParity' => 'odd',
            'groupIds' => [$fixtures->groupId],
        ];

        $rejected = $this->requestJson('POST', sprintf('/api/admin/schedules/%d/entries', $this->intValue($schedule, 'id')), $payload, 422);
        self::assertArrayHasKey('roomId', $this->objectValue($rejected, 'errors'));

        $accepted = $this->requestJson('POST', sprintf('/api/admin/schedules/%d/entries', $this->intValue($schedule, 'id')), [
            ...$payload,
            'roomId' => $fixtures->roomId,
        ], 201);

        self::assertSame($fixtures->roomId, $this->intValue($accepted, 'roomId'));
    }

    public function testSchedulePeriodMustStayWithinSemester(): void
    {
        $fixtures = $this->createScheduleFixtures();

        $result = $this->requestJson('POST', '/api/admin/schedules', [
            'semesterId' => $fixtures->semesterId,
            'validFrom' => '2026-08-31',
            'validTo' => '2026-12-31',
        ], 422);

        self::assertArrayHasKey('validFrom', $this->objectValue($result, 'errors'));
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

    public function testOverlappingTimeSlotDraftEntryIsRejected(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $overlappingSlot = $this->requestJson('POST', '/api/admin/time-slots', [
            'number' => 2,
            'startsAt' => '09:00:00',
            'endsAt' => '10:20:00',
        ], 201);
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
            'timeSlotId' => $this->intValue($overlappingSlot, 'id'),
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
        self::assertSame('draft', $logs[0]->getBeforePayload()['status'] ?? null);
        self::assertSame('published', $logs[0]->getAfterPayload()['status'] ?? null);
        self::assertNotSame([], FakeTelegramNotificationPublisher::$messages);
        self::assertSame('schedule_published', FakeTelegramNotificationPublisher::$messages[0]['eventType']);
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

    public function testAdminCanListScheduleGenerationJobs(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $first = $this->requestJson('POST', '/api/admin/schedules/generate', [
            'semesterId' => $fixtures->semesterId,
        ], 202);
        sleep(1);
        $second = $this->requestJson('POST', '/api/admin/schedules/generate', [
            'semesterId' => $fixtures->semesterId,
        ], 202);

        $list = $this->requestJson('GET', '/api/admin/generation-jobs');
        $items = $this->listValue($list, 'items');
        $latest = $this->objectAt($items, 0);

        self::assertCount(2, $items);
        self::assertSame($this->stringValue($second, 'id'), $this->stringValue($latest, 'id'));
        self::assertSame($fixtures->semesterId, $this->intValue($latest, 'semesterId'));
        self::assertSame('queued', $this->stringValue($latest, 'status'));
        self::assertNull($latest['generatedScheduleId']);
        self::assertSame($this->stringValue($first, 'id'), $this->stringValue($this->objectAt($items, 1), 'id'));
    }

    public function testScheduleGenerationRequiresActiveTeachingLoadsBeforeQueueing(): void
    {
        $academicYear = $this->requestJson('POST', '/api/admin/academic-years', [
            'name' => '2027/2028',
            'startsAt' => '2027-09-01',
            'endsAt' => '2028-06-30',
        ], 201);
        $semester = $this->requestJson('POST', '/api/admin/semesters', [
            'academicYearId' => $this->intValue($academicYear, 'id'),
            'number' => 1,
            'startsAt' => '2027-09-01',
            'endsAt' => '2027-12-31',
            'firstWeekParity' => 'odd',
        ], 201);

        $result = $this->requestJson('POST', '/api/admin/schedules/generate', [
            'semesterId' => $this->intValue($semester, 'id'),
        ], 422);

        self::assertArrayHasKey('semesterId', $this->objectValue($result, 'errors'));
        self::assertNull(FakeScheduleGenerationPublisher::$message);
    }

    public function testAdminCanStartScheduleCompletionGenerationJob(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $schedule = $this->createDraftScheduleWithEntry($fixtures);

        $job = $this->requestJson('POST', '/api/admin/schedules/generate', [
            'semesterId' => $fixtures->semesterId,
            'scheduleId' => $this->intValue($schedule, 'id'),
        ], 202);

        self::assertSame('queued', $this->stringValue($job, 'status'));
        self::assertNotNull(FakeScheduleGenerationPublisher::$message);
        self::assertSame($this->intValue($schedule, 'id'), FakeScheduleGenerationPublisher::$message['baseScheduleId'] ?? null);
    }

    public function testScheduleCompletionGenerationRejectsPublishedSchedule(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $schedule = $this->createDraftScheduleWithEntry($fixtures);
        $scheduleEntity = $this->entityManager->find(Schedule::class, $this->intValue($schedule, 'id'));
        self::assertInstanceOf(Schedule::class, $scheduleEntity);
        $scheduleEntity->setStatus(ScheduleStatus::Published);
        $this->entityManager->flush();

        $result = $this->requestJson('POST', '/api/admin/schedules/generate', [
            'semesterId' => $fixtures->semesterId,
            'scheduleId' => $this->intValue($schedule, 'id'),
        ], 422);

        self::assertArrayHasKey('scheduleId', $this->objectValue($result, 'errors'));
        self::assertNull(FakeScheduleGenerationPublisher::$message);
    }

    public function testScheduleCompletionGenerationRejectsCompleteSchedule(): void
    {
        $fixtures = $this->createScheduleFixtures();
        $this->requestJson('PATCH', sprintf('/api/admin/teaching-loads/%d', $fixtures->teachingLoadId), [
            'requiredLessonCount' => 2,
        ]);
        $schedule = $this->createDraftScheduleWithEntry($fixtures);

        $result = $this->requestJson('POST', '/api/admin/schedules/generate', [
            'semesterId' => $fixtures->semesterId,
            'scheduleId' => $this->intValue($schedule, 'id'),
        ], 422);

        self::assertArrayHasKey('scheduleId', $this->objectValue($result, 'errors'));
        self::assertNull(FakeScheduleGenerationPublisher::$message);
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

    private function actionLog(string $action): ActionLog
    {
        $log = $this->entityManager->getRepository(ActionLog::class)->findOneBy(['action' => $action]);

        if (!$log instanceof ActionLog) {
            throw new \RuntimeException(sprintf('Expected action log "%s" to exist.', $action));
        }

        return $log;
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
        $this->requestJson('POST', '/api/admin/teacher-subjects', [
            'teacherId' => $this->intValue($teacher, 'id'),
            'subjectId' => $this->intValue($subject, 'id'),
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
