<?php

declare(strict_types=1);

namespace App\Tests\Controller;

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
use App\Entity\TelegramSubscription;
use App\Entity\TimeSlot;
use App\Enum\LessonType;
use App\Enum\RoomType;
use App\Enum\ScheduleStatus;
use App\Enum\WeekParity;
use App\Service\AI\TelegramIntent;
use App\Tests\Double\FakeTelegramIntentParser;
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
        $this->client->disableReboot();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        FakeTelegramSender::reset();
        FakeTelegramIntentParser::reset();
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
        self::assertSame(0, FakeTelegramIntentParser::$calls);
    }

    public function testScheduleCommandReturnsPublicSchedule(): void
    {
        $this->createPublishedScheduleFixtures();

        $this->postUpdate('/schedule group КН-22');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('Programming', FakeTelegramSender::$messages[0]['text']);
        self::assertStringContainsString('Lab 1', FakeTelegramSender::$messages[0]['text']);
        self::assertSame('HTML', FakeTelegramSender::$messages[0]['parseMode']);
        self::assertStringContainsString('<b>', FakeTelegramSender::$messages[0]['text']);
    }

    public function testScheduleCommandWithoutArgumentsStartsButtonFlow(): void
    {
        $this->postUpdate('/schedule');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('Оберіть', FakeTelegramSender::$messages[0]['text']);
        self::assertSame('tg:type:schedule:group:0', FakeTelegramSender::$messages[0]['keyboard'][0][0]->callbackData);
        self::assertSame('tg:type:schedule:teacher:0', FakeTelegramSender::$messages[0]['keyboard'][1][0]->callbackData);
        self::assertSame('tg:type:schedule:room:0', FakeTelegramSender::$messages[0]['keyboard'][2][0]->callbackData);
    }

    public function testScheduleTypeCallbackShowsPagedEntityButtons(): void
    {
        $this->createPublishedScheduleFixtures();
        $this->postUpdate('/schedule');

        $this->postCallback('tg:type:schedule:group:0');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('Оберіть групу', FakeTelegramSender::$messages[1]['text']);
        self::assertSame('tg:pick:schedule:group:', substr(FakeTelegramSender::$messages[1]['keyboard'][0][0]->callbackData, 0, 23));
        self::assertSame([['id' => 'callback-1', 'text' => null]], FakeTelegramSender::$callbackAnswers);
    }

    public function testScheduleEntityCallbackSendsScheduleWithWeekNavigation(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures();
        $this->postUpdate('/schedule');
        $this->postCallback('tg:type:schedule:group:0');

        $this->postCallback(sprintf('tg:pick:schedule:group:%d', $fixtures->group->getId()), id: 'callback-2');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('Programming', FakeTelegramSender::$messages[2]['text']);
        self::assertSame('Попередній', FakeTelegramSender::$messages[2]['keyboard'][0][0]->text);
        self::assertStringContainsString('tg:week:group:', FakeTelegramSender::$messages[2]['keyboard'][0][0]->callbackData);
    }

    public function testWeekNavigationCallbackSendsAdjustedWeekSchedule(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures();

        $this->postCallback(sprintf('tg:week:group:%d:2026-05-18:-1', $fixtures->group->getId()));

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('<b>Понеділок (11.05)</b>', FakeTelegramSender::$messages[0]['text']);
        self::assertStringContainsString('Programming', FakeTelegramSender::$messages[0]['text']);
        self::assertSame('HTML', FakeTelegramSender::$messages[0]['parseMode']);
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

    public function testSubscribeButtonFlowCreatesSubscription(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures();
        $this->postUpdate('/subscribe');

        self::assertSame('tg:type:subscribe:group:0', FakeTelegramSender::$messages[0]['keyboard'][0][0]->callbackData);
        self::assertSame('tg:type:subscribe:teacher:0', FakeTelegramSender::$messages[0]['keyboard'][1][0]->callbackData);
        self::assertSame('tg:cancel', FakeTelegramSender::$messages[0]['keyboard'][2][0]->callbackData);

        $this->postCallback('tg:type:subscribe:group:0');
        $this->postCallback(sprintf('tg:pick:subscribe:group:%d', $fixtures->group->getId()), id: 'callback-2');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('створено', FakeTelegramSender::$messages[2]['text']);
        self::assertCount(1, $this->entityManager->getRepository(TelegramSubscription::class)->findBy([
            'telegramChatId' => 123456,
            'entityType' => 'group',
            'entityId' => $fixtures->group->getId(),
        ]));
    }

    public function testSubscribeButtonFlowReportsDuplicateSubscription(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures();
        $this->entityManager->persist(new TelegramSubscription(123456, 'group', (int) $fixtures->group->getId(), new \DateTimeImmutable()));
        $this->entityManager->flush();
        $this->postUpdate('/subscribe');
        $this->postCallback('tg:type:subscribe:group:0');

        $this->postCallback(sprintf('tg:pick:subscribe:group:%d', $fixtures->group->getId()), id: 'callback-2');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('вже існує', FakeTelegramSender::$messages[2]['text']);
        self::assertCount(1, $this->entityManager->getRepository(TelegramSubscription::class)->findBy([
            'telegramChatId' => 123456,
            'entityType' => 'group',
            'entityId' => $fixtures->group->getId(),
        ]));
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

    public function testUnsubscribeButtonFlowRemovesSubscription(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures();
        $this->entityManager->persist(new TelegramSubscription(123456, 'teacher', (int) $fixtures->teacher->getId(), new \DateTimeImmutable()));
        $this->entityManager->flush();

        $this->postUpdate('/unsubscribe');
        $this->postCallback(sprintf('tg:pick:unsubscribe:teacher:%d', $fixtures->teacher->getId()));

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('видалено', FakeTelegramSender::$messages[1]['text']);
        self::assertCount(0, $this->entityManager->getRepository(TelegramSubscription::class)->findAll());
    }

    public function testMalformedCallbackIsAnsweredWithoutMutation(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures();

        $this->postCallback(sprintf('tg:pick:subscribe:group:%d', $fixtures->group->getId()), chatId: 654321);

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('Кнопка застаріла', FakeTelegramSender::$messages[0]['text']);
        self::assertSame('Запит не вдалося виконати.', FakeTelegramSender::$callbackAnswers[0]['text']);
        self::assertCount(0, $this->entityManager->getRepository(TelegramSubscription::class)->findAll());
    }

    public function testFreeTextScheduleLookupUsesAiIntent(): void
    {
        $this->createPublishedScheduleFixtures();
        FakeTelegramIntentParser::$intent = $this->intent('get_schedule', 'group', 'КН-22', date: '2026-05-12', range: 'tomorrow');

        $this->postUpdate('Покажи розклад КН-22 завтра');

        self::assertResponseStatusCodeSame(204);
        self::assertSame(1, FakeTelegramIntentParser::$calls);
        self::assertStringContainsString('<b>Понеділок (11.05)</b>', FakeTelegramSender::$messages[0]['text']);
        self::assertStringContainsString('Programming', FakeTelegramSender::$messages[0]['text']);
        self::assertSame('HTML', FakeTelegramSender::$messages[0]['parseMode']);
    }

    public function testFreeTextRoomScheduleLookupUsesAiIntent(): void
    {
        $this->createPublishedScheduleFixtures();
        FakeTelegramIntentParser::$intent = $this->intent('get_schedule', 'room', 'Lab 1', weekStart: '2026-05-11', range: 'week');

        $this->postUpdate('Що в аудиторії Lab 1 цього тижня?');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('Programming', FakeTelegramSender::$messages[0]['text']);
        self::assertStringContainsString('Lab 1', FakeTelegramSender::$messages[0]['text']);
    }

    public function testFreeTextSubscribeUsesAiIntent(): void
    {
        $fixtures = $this->createPublishedScheduleFixtures();
        FakeTelegramIntentParser::$intent = $this->intent('subscribe', 'teacher', 'John Doe');

        $this->postUpdate('Підпиши мене на викладача John Doe');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('створено', FakeTelegramSender::$messages[0]['text']);
        self::assertCount(1, $this->entityManager->getRepository(TelegramSubscription::class)->findBy([
            'telegramChatId' => 123456,
            'entityType' => 'teacher',
            'entityId' => $fixtures->teacher->getId(),
        ]));
    }

    public function testFreeTextRoomSubscribeIsRejected(): void
    {
        $this->createPublishedScheduleFixtures();
        FakeTelegramIntentParser::$intent = $this->intent('subscribe', 'room', 'Lab 1');

        $this->postUpdate('Підпиши мене на аудиторію Lab 1');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('Підписки доступні лише для груп і викладачів', FakeTelegramSender::$messages[0]['text']);
    }

    public function testLowConfidenceAiIntentReturnsClarification(): void
    {
        FakeTelegramIntentParser::$intent = $this->intent('get_schedule', 'group', 'КН-22', confidence: 0.4, clarificationQuestion: 'Уточніть групу.');

        $this->postUpdate('розклад');

        self::assertResponseStatusCodeSame(204);
        self::assertSame('Уточніть групу.', FakeTelegramSender::$messages[0]['text']);
    }

    public function testAiProviderFailureReturnsTemporaryUnavailableMessage(): void
    {
        FakeTelegramIntentParser::$throws = true;

        $this->postUpdate('Покажи розклад');

        self::assertResponseStatusCodeSame(204);
        self::assertStringContainsString('AI-помічник тимчасово недоступний', FakeTelegramSender::$messages[0]['text']);
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

    private function postCallback(string $data, string $secret = 'test-secret', string $id = 'callback-1', int $chatId = 123456): void
    {
        $this->client->jsonRequest('POST', '/api/telegram/webhook', [
            'update_id' => 2,
            'callback_query' => [
                'id' => $id,
                'data' => $data,
                'message' => [
                    'message_id' => 10,
                    'chat' => ['id' => $chatId],
                ],
            ],
        ], [
            'HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN' => $secret,
        ]);
    }

    private function intent(
        string $name,
        ?string $targetType = null,
        ?string $targetName = null,
        float $confidence = 0.95,
        ?string $date = null,
        ?string $weekStart = null,
        ?string $range = null,
        ?string $clarificationQuestion = null,
    ): TelegramIntent {
        $intent = new TelegramIntent();
        $intent->intent = $name;
        $intent->confidence = $confidence;
        $intent->targetType = $targetType;
        $intent->targetName = $targetName;
        $intent->date = $date;
        $intent->weekStart = $weekStart;
        $intent->range = $range;
        $intent->clarificationQuestion = $clarificationQuestion;

        return $intent;
    }

    private function createPublishedScheduleFixtures(): TelegramScheduleFixtures
    {
        $admin = new User('Ada', 'Lovelace', 'admin@example.com', 'hash', new \DateTimeImmutable('2026-01-01'), UserRole::Admin);
        $academicYear = new AcademicYear('2025/2026', new \DateTimeImmutable('2025-09-01'), new \DateTimeImmutable('2026-06-30'));
        $semester = new Semester($academicYear, 2, new \DateTimeImmutable('2026-05-11'), new \DateTimeImmutable('2026-06-30'), WeekParity::Odd);
        $group = new StudentGroup('КН-22', 'Computer Science', 4, 24);
        $teacher = new Teacher('John', 'Doe', 'Computer Science');
        $subject = new Subject('Programming');
        $room = new Room('Lab 1', RoomType::Computer, 30);
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

        return new TelegramScheduleFixtures($group, $teacher);
    }
}

final readonly class TelegramScheduleFixtures
{
    public function __construct(
        public StudentGroup $group,
        public Teacher $teacher,
    ) {}
}
