<?php

declare(strict_types=1);

namespace App\Tests\Service\Telegram;

use App\Resource\Public\PublicScheduleResource;
use App\Resource\Public\ScheduleGroupResource;
use App\Resource\Public\ScheduleItemResource;
use App\Resource\Public\ScheduleRoomResource;
use App\Resource\Public\ScheduleTeacherResource;
use App\Resource\Public\SubjectResource;
use App\Resource\Public\TimeSlotResource;
use App\Service\Telegram\FormatTelegramScheduleService;
use PHPUnit\Framework\TestCase;

final class FormatTelegramScheduleServiceTest extends TestCase
{
    public function testFormatsScheduleGroupedByDayWithHtml(): void
    {
        $schedule = new PublicScheduleResource(
            '2026-04-06',
            'group',
            1,
            [
                new ScheduleItemResource(
                    1,
                    '2026-04-06',
                    1,
                    'laboratory',
                    new TimeSlotResource(1, 3, '11:40', '13:00'),
                    new SubjectResource(1, 'Технології розробки стартапів'),
                    new ScheduleTeacherResource(1, 'Олег', 'Ізвалов'),
                    new ScheduleRoomResource(1, '2', 'lecture'),
                    [new ScheduleGroupResource(1, 'KN-22')],
                    false,
                    false,
                ),
                new ScheduleItemResource(
                    2,
                    '2026-04-07',
                    2,
                    'laboratory',
                    new TimeSlotResource(2, 4, '13:10', '14:30'),
                    new SubjectResource(2, 'Веб-додатки React.JS'),
                    new ScheduleTeacherResource(2, 'Олена', 'Баранюк'),
                    new ScheduleRoomResource(2, '16', 'computer'),
                    [new ScheduleGroupResource(1, 'KN-22')],
                    false,
                    false,
                ),
            ],
        );

        $text = (new FormatTelegramScheduleService())->handle($schedule);

        self::assertStringContainsString('🌵 <b>Понеділок (06.04)</b>', $text);
        self::assertStringContainsString('1️⃣ <b>11:40-13:00</b> <b>Технології розробки стартапів</b> |2| <i>(Ізвалов О.)</i>', $text);
        self::assertStringContainsString('🌳 <b>Вівторок (07.04)</b>', $text);
        self::assertStringContainsString('1️⃣ <b>13:10-14:30</b> <b>Веб-додатки React.JS</b> |16| <i>(Баранюк О.)</i>', $text);
    }

    public function testCancelledLessonIsMarked(): void
    {
        $schedule = new PublicScheduleResource(
            '2026-04-06',
            'group',
            1,
            [
                new ScheduleItemResource(
                    1,
                    '2026-04-06',
                    1,
                    'lecture',
                    new TimeSlotResource(1, 1, '08:30', '10:00'),
                    new SubjectResource(1, 'Math'),
                    new ScheduleTeacherResource(1, 'Ada', 'Lovelace'),
                    new ScheduleRoomResource(1, '101', 'lecture'),
                    [],
                    true,
                    false,
                ),
            ],
        );

        $text = (new FormatTelegramScheduleService())->handle($schedule);

        self::assertStringContainsString('<i>(скасовано)</i>', $text);
    }

    public function testEmptyScheduleReturnsPlainTextMessage(): void
    {
        $schedule = new PublicScheduleResource('2026-04-06', 'group', 1, []);

        self::assertSame(
            'На вибраний тиждень занять не знайдено.',
            (new FormatTelegramScheduleService())->handle($schedule),
        );
    }
}
