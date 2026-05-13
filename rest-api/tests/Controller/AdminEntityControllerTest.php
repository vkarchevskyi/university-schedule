<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Admin;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminEntityControllerTest extends WebTestCase
{
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
    }

    public function testAdminEntityRoutesRequireAuthentication(): void
    {
        $this->client->request('GET', '/api/admin/groups');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGroupCrud(): void
    {
        $payload = $this->requestJson('POST', '/api/admin/groups', [
            'name' => 'KN-22',
            'speciality' => 'Computer Science',
            'course' => 4,
            'studentCount' => 24,
        ], 201);

        self::assertSame('KN-22', $payload['name']);

        $updated = $this->requestJson('PATCH', '/api/admin/groups/' . $payload['id'], [
            'studentCount' => 25,
        ]);

        self::assertSame(25, $updated['studentCount']);

        $list = $this->requestJson('GET', '/api/admin/groups');

        self::assertCount(1, $list['items']);
    }

    public function testInvalidTimeSlotReturnsValidationError(): void
    {
        $payload = $this->requestJson('POST', '/api/admin/time-slots', [
            'number' => 1,
            'startsAt' => '10:00',
            'endsAt' => '09:00',
        ], 422);

        self::assertArrayHasKey('endsAt', $payload['errors']);
    }

    public function testDtoValidationRejectsMissingFields(): void
    {
        $payload = $this->requestJson('POST', '/api/admin/groups', [
            'name' => 'KN-22',
            'course' => 4,
        ], 422);

        self::assertArrayHasKey('speciality', $payload['errors']);
        self::assertArrayHasKey('studentCount', $payload['errors']);
    }

    public function testDtoValidationRejectsWronglyTypedFields(): void
    {
        $payload = $this->requestJson('POST', '/api/admin/groups', [
            'name' => 'KN-22',
            'speciality' => 'Computer Science',
            'course' => '4',
            'studentCount' => 24,
        ], 422);

        self::assertArrayHasKey('course', $payload['errors']);
    }

    public function testPatchRejectsEmptyPayloadAndExplicitNull(): void
    {
        $group = $this->requestJson('POST', '/api/admin/groups', [
            'name' => 'KN-22',
            'speciality' => 'Computer Science',
            'course' => 4,
            'studentCount' => 24,
        ], 201);

        $emptyPatch = $this->requestJson('PATCH', '/api/admin/groups/' . $group['id'], [], 422);
        self::assertArrayHasKey('json', $emptyPatch['errors']);

        $nullPatch = $this->requestJson('PATCH', '/api/admin/groups/' . $group['id'], [
            'name' => null,
        ], 422);
        self::assertArrayHasKey('json', $nullPatch['errors']);
    }

    public function testTeachingLoadCanBeCreatedForSemesterGroupSubjectAndTeacher(): void
    {
        $academicYear = $this->requestJson('POST', '/api/admin/academic-years', [
            'name' => '2026/2027',
            'startsAt' => '2026-09-01',
            'endsAt' => '2027-06-30',
        ], 201);
        $semester = $this->requestJson('POST', '/api/admin/semesters', [
            'academicYearId' => $academicYear['id'],
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

        $teacherSubject = $this->requestJson('POST', '/api/admin/teacher-subjects', [
            'teacherId' => $teacher['id'],
            'subjectId' => $subject['id'],
        ], 201);

        self::assertSame($teacher['id'], $teacherSubject['teacherId']);

        $teachingLoad = $this->requestJson('POST', '/api/admin/teaching-loads', [
            'semesterId' => $semester['id'],
            'groupId' => $group['id'],
            'subjectId' => $subject['id'],
            'teacherId' => $teacher['id'],
            'lessonType' => 'laboratory',
            'requiredLessonCount' => 8,
        ], 201);

        self::assertSame('laboratory', $teachingLoad['lessonType']);
        self::assertSame(8, $teachingLoad['requiredLessonCount']);

        $deleted = $this->requestJson('DELETE', '/api/admin/teaching-loads/' . $teachingLoad['id'], expectedStatus: 204);

        self::assertNull($deleted);
    }

    /** @param array<string, mixed> $payload @return array<string, mixed>|null */
    private function requestJson(string $method, string $uri, array $payload = [], int $expectedStatus = 200): ?array
    {
        $server = ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $this->token)];

        if ($method === 'GET' || $method === 'DELETE') {
            $this->client->request($method, $uri, server: $server);
        } else {
            $this->client->jsonRequest($method, $uri, $payload, server: $server);
        }

        self::assertResponseStatusCodeSame($expectedStatus);

        if ($expectedStatus === 204) {
            return null;
        }

        return json_decode($this->client->getResponse()->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR);
    }

    private function login(): string
    {
        $passwordHash = password_hash('correct-password', PASSWORD_BCRYPT);

        self::assertIsString($passwordHash);

        $admin = new Admin('Ada', 'Lovelace', 'admin@example.com', $passwordHash, new \DateTimeImmutable());
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $this->client->jsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR);

        return $payload['token'];
    }
}
