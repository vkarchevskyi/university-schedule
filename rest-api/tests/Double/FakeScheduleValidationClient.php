<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\Resource\Admin\ScheduleValidationConflictResource;
use App\Resource\Admin\ScheduleValidationResource;
use App\Service\ScheduleValidation\ScheduleValidationClientInterface;

final class FakeScheduleValidationClient implements ScheduleValidationClientInterface
{
    private static ?ScheduleValidationResource $result = null;

    /** @var array<string, mixed>|null */
    public static ?array $payload = null;

    /** @param array<string, mixed> $payload */
    public function validate(array $payload): ScheduleValidationResource
    {
        self::$payload = $payload;

        return self::$result ?? new ScheduleValidationResource(true, []);
    }

    /** @param list<int> $entryIds */
    public static function rejectResult(string $type = 'teacher_conflict', string $message = 'Schedule is invalid.', array $entryIds = []): void
    {
        self::$result = new ScheduleValidationResource(false, [
            new ScheduleValidationConflictResource($type, $message, $entryIds),
        ]);
    }

    public static function resetResult(): void
    {
        self::reset();
    }

    private static function reset(): void
    {
        self::$result = new ScheduleValidationResource(true, []);
        self::$payload = null;
    }
}
