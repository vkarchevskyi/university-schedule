<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Service\ScheduleGeneration\ScheduleGenerationPublisherInterface;

final class FakeScheduleGenerationPublisher implements ScheduleGenerationPublisherInterface
{
    /** @var array{jobId: string, semesterId: int, requestedByUserId: int, baseScheduleId?: int}|null */
    public static ?array $message = null;

    public function publish(array $message): void
    {
        self::$message = $message;
    }

    public static function reset(): void
    {
        self::$message = null;
    }
}
