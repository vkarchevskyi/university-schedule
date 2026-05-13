<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Service\ExamScheduleGeneration\ExamScheduleGenerationPublisherInterface;

final class FakeExamScheduleGenerationPublisher implements ExamScheduleGenerationPublisherInterface
{
    /** @var array{jobId: string, semesterId: int, requestedByAdminId: int}|null */
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
