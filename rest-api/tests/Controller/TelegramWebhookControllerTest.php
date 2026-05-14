<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AcademicYear;
use App\Entity\Admin;
use App\Entity\Group as StudentGroup;
use App\Entity\Room;
use App\Entity\Schedule;
use App\Entity\ScheduleEntry;
use App\Entity\ScheduleEntryGroup;
use App\Entity\Semester;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\TelegramSubscription;
use App\Entity\TimeSlot;
use App\Enum\LessonType;
use App\Enum\ScheduleStatus;
use App\Enum\WeekParity;
use App\Tests\Double\FakeTelegramSender;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TelegramWebhookControllerTest extends WebTestCase
{
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

        FakeTelegramSender::reset();
    }

    public function testWebhookRejectsInvalidSecret(): void
    {
        $this->postUpdate('/start', secret: 'wrong-secret');

        self::assertResponseStatusCodeSame(401);
        self::assertSame([], FakeTelegramSender::$messages);
    }

    public function testStartCommandReturnsUkrainianHelp(): void
    {
        $this->postUpdate('/start');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('/schedule group КН-22', FakeTelegramSender::$messages[0]['text']);
    }

    public function testScheduleCommandReturnsPublicSchedule(): void
    {
        $this->createPublishedScheduleFixtures();

        $this->postUpdate('/schedule group КН-22');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('Programming', FakeTelegramSender::$messages[0]['text']);
        self::assertStringContainsString('Lab 1', FakeTelegramSender::$messages[0]['text']);
    }

    public function testSubscribePreventsDuplicates(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures();

        $this->postUpdate('/subscribe group КН-22');
        $this->postUpdate('/subscribe group КН-22');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('створено', FakeTelegramSender::$messages[0]['text']);
        self::assertStringContainsString('вже існує', FakeTelegramSender::$messages[1]['text']);

        $subscriptions = $this->entityManager->getRepository(TelegramSubscription::class)->findBy([
            'telegramChatId' => 123456,
            'entityType' => 'group',
            'entityId' => $fixtures->group->getId(),
        ]);

        self::assertCount(1, $subscriptions);
    }

    public function testUnsubscribeRemovesSubscription(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures();
        $subscription = new TelegramSubscription(123456, 'group', (int) $fixtures->group->getId(), new \DateTimeImmutable());
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        $this->postUpdate('/unsubscribe group КН-22');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('видалено', FakeTelegramSender::$messages[0]['text']);
        self::assertCount(0, $this->entityManager->getRepository(TelegramSubscription::class)->findAll());
    }

    private function postUpdate(string $text, string $secret = 'test-secret'): void
    {
        $this->client->jsonRequest('POST', '/api/telegram/webhook', [
            'update_id' => 1,
            'message' => [
                'message_id' => 10,
                'text' => $text,
                'chat' => ['id' => 123456],
            ],
        ], [
            'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => $secret,
        ]);
    }

    private function createPublishedScheduleFixtures(): TelegramScheduleFixtures
    {
        $admin = new Admin('Ada', 'Lovelace', 'admin@example.com', 'hash', new \DateTimeImmutable('2026-01-01'));
        $academicYear = new AcademicYear('2025/2026', new \DateTimeImmutable('2025-09-01'), new \DateTimeImmutable('2026-06-30'));
        $semester = new Semester($academicYear, 2, new \DateTimeImmutable('2026-05-11'), new \DateTimeImmutable('2026-06-30'), WeekParity::Odd);
        $group = new StudentGroup('КН-22', 'Computer Science', 4, 24);
        $teacher = new Teacher('John', 'Doe', 'Computer Science');
        $subject = new Subject('Programming');
        $room = new Room('Lab 1', 'computer', 30);
        $timeSlot = new TimeSlot(1, new \DateTimeImmutable('08:30'), new \DateTimeImmutable('10:00'));
        $schedule = new Schedule(
            $semester,
            ScheduleStatus::Published,
            new \DateTimeImmutable('2026-05-11'),
            new \DateTimeImmutable('2026-06-30'),
            $admin,
            new \DateTimeImmutable('2026-05-10T10:00:00+00:00'),
            new \DateTimeImmutable('2026-05-10T11:00:00+00:00'),
        );
        $entry = new ScheduleEntry($schedule, $subject, $teacher, LessonType::Laboratory, $room, $timeSlot, 1, WeekParity::Both);
        $entryGroup = new ScheduleEntryGroup($entry, $group);
        $schedule->addEntry($entry);
        $entry->addGroup($entryGroup);

        foreach ([$admin, $academicYear, $semester, $group, $teacher, $subject, $room, $timeSlot, $schedule, $entry, $entryGroup] as $entity) {
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();

        return new TelegramScheduleFixtures($group);
    }
}

final readonly class TelegramScheduleFixtures
{
    public function __construct(public StudentGroup $group) {}
}
